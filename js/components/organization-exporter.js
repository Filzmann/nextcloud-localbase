(function() {
    'use strict';

    const xml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&apos;');

    const pdfNumber = value => Number(value.toFixed(3)).toString();
    const winAnsi = new Map([
        [0x20ac, 128], [0x201a, 130], [0x201e, 132], [0x2026, 133], [0x2018, 145],
        [0x2019, 146], [0x201c, 147], [0x201d, 148], [0x2022, 149], [0x2013, 150], [0x2014, 151],
    ]);
    const pdfText = value => [...String(value ?? '')].map(character => {
        const codePoint = character.codePointAt(0);
        if (character === '\\' || character === '(' || character === ')') return `\\${character}`;
        if (codePoint >= 32 && codePoint <= 126) return character;
        const encoded = codePoint >= 160 && codePoint <= 255 ? codePoint : winAnsi.get(codePoint);
        return encoded === undefined ? '?' : `\\${encoded.toString(8).padStart(3, '0')}`;
    }).join('');

    /**
     * Zweck: Exportiert den aktuell sichtbaren Organigrammstand ohne Serverablage oder externe Dienste.
     * Vertrag: Personenbezüge werden ausschließlich nach ausdrücklicher Aktivierung in den Export-Snapshot aufgenommen.
     */
    class OrganizationExporter {
        constructor({ container, board }) {
            this.container = container;
            this.board = board;
            container.addEventListener('click', event => this.onClick(event));
        }

        markup() {
            return `<div class="orgs-export" role="group" aria-label="Organigramm exportieren">
                <strong>Organigramm exportieren</strong>
                <label><input type="checkbox" data-organization-export-people> Zugeordnete Nutzer*innen einbeziehen</label>
                <div class="orgs-export-actions">
                    <button type="button" data-action="export-drawio">Draw.io</button>
                    <button type="button" data-action="export-png">PNG</button>
                    <button type="button" data-action="export-pdf">PDF</button>
                </div>
                <span class="orgs-export-help">Exportiert wird der aktuell sichtbare Stand. Alle Formate werden direkt als Datei heruntergeladen.</span>
                <p class="orgs-feedback" data-organization-export-feedback role="status" aria-live="polite"></p>
            </div>`;
        }

        async onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action^="export-"]') : null;
            if (!button) return;
            const includePeople = Boolean(this.container.querySelector('[data-organization-export-people]')?.checked);
            const snapshot = this.board.exportSnapshot(includePeople);
            if (!snapshot.nodes.length) return this.feedback('Das Organigramm enthält keine exportierbaren Karten.');
            const layout = this.layout(snapshot);
            button.disabled = true;
            try {
                if (button.dataset.action === 'export-drawio') this.downloadBlob(new Blob([this.toDrawio(layout)], { type: 'application/vnd.jgraph.mxfile' }), 'ad-organigramm.drawio');
                if (button.dataset.action === 'export-png') await this.exportPng(layout);
                if (button.dataset.action === 'export-pdf') this.downloadBlob(new Blob([this.toPdf(layout)], { type: 'application/pdf' }), 'ad-organigramm.pdf');
                this.feedback('Das Organigramm wurde exportiert.');
            } catch (error) {
                this.feedback(error instanceof Error ? error.message : 'Das Organigramm konnte nicht exportiert werden.');
            } finally {
                button.disabled = false;
            }
        }

        feedback(message) {
            const target = this.container.querySelector('[data-organization-export-feedback]');
            if (target) target.textContent = message;
        }

        layout(snapshot) {
            const nodeWidth = 260;
            const nodeHeight = 120;
            const levelGap = 100;
            const sideGap = 80;
            const levels = new Map();
            for (const node of snapshot.nodes) levels.set(node.level, [...(levels.get(node.level) || []), node]);
            const orderedLevels = [...levels.entries()].sort(([a], [b]) => a - b);
            const maxCount = Math.max(1, ...orderedLevels.map(([, nodes]) => nodes.length));
            const width = Math.max(640, maxCount * nodeWidth + (maxCount + 1) * sideGap);
            const nodes = [];
            orderedLevels.forEach(([, levelNodes], levelIndex) => {
                const spacing = (width - levelNodes.length * nodeWidth) / (levelNodes.length + 1);
                levelNodes.forEach((node, index) => nodes.push({
                    ...node,
                    x: spacing + index * (nodeWidth + spacing),
                    y: 50 + levelIndex * (nodeHeight + levelGap),
                    width: nodeWidth,
                    height: nodeHeight,
                }));
            });
            const height = 90 + orderedLevels.length * nodeHeight + Math.max(0, orderedLevels.length - 1) * levelGap;
            return { title: snapshot.title, width, height, nodes, edges: snapshot.edges };
        }

        nodeLines(node) {
            return [node.label, node.areaLabel ? `Bereich ${node.areaLabel}` : null, node.personLabel].filter(Boolean);
        }

        textWidth(value, fontSize, bold = false) {
            const units = [...String(value ?? '')].reduce((width, character) => {
                if (character === ' ') return width + 0.28;
                if (/[ilI.,'!:;]/u.test(character)) return width + 0.28;
                if (/[mwMW@%&]/u.test(character)) return width + 0.9;
                if (/[A-ZÄÖÜ]/u.test(character)) return width + 0.7;
                if (/[0-9]/u.test(character)) return width + 0.56;
                return width + 0.57;
            }, 0);
            return units * fontSize * (bold ? 1.04 : 1);
        }

        wrapText(value, maxWidth, fontSize, bold = false) {
            const lines = [];
            let current = '';
            for (const word of String(value ?? '').trim().split(/\s+/u).filter(Boolean)) {
                const candidate = current ? `${current} ${word}` : word;
                if (this.textWidth(candidate, fontSize, bold) <= maxWidth) {
                    current = candidate;
                    continue;
                }
                if (current) lines.push(current);
                current = '';
                if (this.textWidth(word, fontSize, bold) <= maxWidth) {
                    current = word;
                    continue;
                }
                for (const character of word) {
                    if (current && this.textWidth(`${current}${character}`, fontSize, bold) > maxWidth) {
                        lines.push(current);
                        current = character;
                    } else {
                        current += character;
                    }
                }
            }
            if (current) lines.push(current);
            return lines.length ? lines : [''];
        }

        nodeTextLayout(node) {
            const maxWidth = node.width - 28;
            const topPadding = 18;
            const bottomPadding = 8;
            let result = null;
            for (let fontSize = 16; fontSize >= 8; fontSize -= 1) {
                const lineHeight = fontSize * 1.25;
                const lines = this.nodeLines(node).flatMap((value, sourceIndex) => this.wrapText(value, maxWidth, fontSize, sourceIndex === 0).map(text => ({ text, bold: sourceIndex === 0 })));
                result = {
                    fontSize,
                    lines: lines.map((line, index) => ({ ...line, baseline: topPadding + fontSize + index * lineHeight })),
                };
                const last = result.lines.at(-1);
                if (!last || last.baseline + fontSize * 0.25 <= node.height - bottomPadding) return result;
            }
            return result;
        }

        toDrawio(layout) {
            const ids = new Map(layout.nodes.map((node, index) => [node.id, `node-${index + 1}`]));
            const vertices = layout.nodes.map(node => {
                const value = this.nodeLines(node).map(xml).join('&#xa;');
                return `<mxCell id="${ids.get(node.id)}" value="${value}" style="rounded=1;whiteSpace=wrap;html=0;strokeWidth=2;fillColor=#ffffff;strokeColor=#8c8c8c;fontFamily=Arial;" vertex="1" parent="1"><mxGeometry x="${Math.round(node.x)}" y="${Math.round(node.y)}" width="${node.width}" height="${node.height}" as="geometry"/></mxCell>`;
            }).join('');
            const edges = layout.edges.map((edge, index) => {
                const source = ids.get(edge.source);
                const target = ids.get(edge.destination);
                if (!source || !target) return '';
                return `<mxCell id="edge-${index + 1}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=0;strokeWidth=2;endArrow=block;" edge="1" parent="1" source="${source}" target="${target}"><mxGeometry relative="1" as="geometry"/></mxCell>`;
            }).join('');
            return `<?xml version="1.0" encoding="UTF-8"?><mxfile host="app.diagrams.net" compressed="false"><diagram id="ad-organigramm" name="Organigramm"><mxGraphModel dx="${layout.width}" dy="${layout.height}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="${layout.width}" pageHeight="${layout.height}"><root><mxCell id="0"/><mxCell id="1" parent="0"/>${vertices}${edges}</root></mxGraphModel></diagram></mxfile>`;
        }

        toSvg(layout) {
            const nodes = new Map(layout.nodes.map(node => [node.id, node]));
            const edges = layout.edges.map(edge => {
                const source = nodes.get(edge.source);
                const target = nodes.get(edge.destination);
                if (!source || !target) return '';
                const fromX = source.x + source.width / 2;
                const fromY = source.y + source.height;
                const toX = target.x + target.width / 2;
                const toY = target.y;
                const middleY = fromY + Math.max(20, (toY - fromY) / 2);
                return `<path d="M ${fromX} ${fromY} C ${fromX} ${middleY}, ${toX} ${middleY}, ${toX} ${toY - 4}" fill="none" stroke="#00679e" stroke-width="2.5" marker-end="url(#arrow)"/>`;
            }).join('');
            const clips = layout.nodes.map((node, index) => `<clipPath id="node-clip-${index}"><rect x="${node.x + 8}" y="${node.y + 8}" width="${node.width - 16}" height="${node.height - 16}"/></clipPath>`).join('');
            const cards = layout.nodes.map((node, index) => {
                const textLayout = this.nodeTextLayout(node);
                const text = textLayout.lines.map(line => `<tspan x="${node.x + 14}" y="${node.y + line.baseline}"${line.bold ? ' font-weight="700"' : ''}>${xml(line.text)}</tspan>`).join('');
                return `<g><rect x="${node.x}" y="${node.y}" width="${node.width}" height="${node.height}" rx="7" fill="#ffffff" stroke="#8c8c8c" stroke-width="2"/><text clip-path="url(#node-clip-${index})" fill="#1f1f1f" font-family="Arial, sans-serif" font-size="${textLayout.fontSize}">${text}</text></g>`;
            }).join('');
            return `<svg xmlns="http://www.w3.org/2000/svg" width="${layout.width}" height="${layout.height}" viewBox="0 0 ${layout.width} ${layout.height}" role="img" aria-label="${xml(layout.title)}"><rect width="100%" height="100%" fill="#ffffff"/><defs><marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M 0 0 L 10 5 L 0 10 z" fill="#00679e"/></marker>${clips}</defs><text x="24" y="30" fill="#1f1f1f" font-family="Arial, sans-serif" font-size="20" font-weight="700">${xml(layout.title)}</text>${edges}${cards}</svg>`;
        }

        toPdf(layout) {
            const margin = 28;
            const preferredScale = 0.75;
            const pageWidth = Math.min(14400, Math.max(841.89, layout.width * preferredScale + margin * 2));
            const pageHeight = Math.min(14400, Math.max(595.28, layout.height * preferredScale + margin * 2));
            const scale = Math.min(preferredScale, (pageWidth - margin * 2) / layout.width, (pageHeight - margin * 2) / layout.height);
            const offsetX = (pageWidth - layout.width * scale) / 2;
            const offsetY = (pageHeight - layout.height * scale) / 2;
            const x = value => offsetX + value * scale;
            const y = value => pageHeight - offsetY - value * scale;
            const nodes = new Map(layout.nodes.map(node => [node.id, node]));
            const commands = ['1 1 1 rg', `0 0 ${pdfNumber(pageWidth)} ${pdfNumber(pageHeight)} re f`];

            commands.push('0 0.404 0.62 RG', '0 0.404 0.62 rg', `${pdfNumber(Math.max(0.5, 2.5 * scale))} w`);
            for (const edge of layout.edges) {
                const source = nodes.get(edge.source);
                const target = nodes.get(edge.destination);
                if (!source || !target) continue;
                const fromX = source.x + source.width / 2;
                const fromY = source.y + source.height;
                const toX = target.x + target.width / 2;
                const toY = target.y - 4;
                const middleY = fromY + Math.max(20, (toY - fromY) / 2);
                commands.push(`${pdfNumber(x(fromX))} ${pdfNumber(y(fromY))} m ${pdfNumber(x(fromX))} ${pdfNumber(y(middleY))} ${pdfNumber(x(toX))} ${pdfNumber(y(middleY))} ${pdfNumber(x(toX))} ${pdfNumber(y(toY))} c S`);
                commands.push(`${pdfNumber(x(toX))} ${pdfNumber(y(toY))} m ${pdfNumber(x(toX - 5))} ${pdfNumber(y(toY - 10))} l ${pdfNumber(x(toX + 5))} ${pdfNumber(y(toY - 10))} l h f`);
            }

            for (const node of layout.nodes) {
                commands.push('1 1 1 rg', '0.55 0.55 0.55 RG', `${pdfNumber(Math.max(0.5, 2 * scale))} w`);
                commands.push(`${pdfNumber(x(node.x))} ${pdfNumber(y(node.y + node.height))} ${pdfNumber(node.width * scale)} ${pdfNumber(node.height * scale)} re B`);
                const textLayout = this.nodeTextLayout(node);
                commands.push(`q ${pdfNumber(x(node.x + 8))} ${pdfNumber(y(node.y + node.height - 8))} ${pdfNumber((node.width - 16) * scale)} ${pdfNumber((node.height - 16) * scale)} re W n`);
                textLayout.lines.forEach(line => {
                    const font = line.bold ? 'F2' : 'F1';
                    commands.push('0.12 0.12 0.12 rg', `BT /${font} ${pdfNumber(textLayout.fontSize * scale)} Tf 1 0 0 1 ${pdfNumber(x(node.x + 14))} ${pdfNumber(y(node.y + line.baseline))} Tm (${pdfText(line.text)}) Tj ET`);
                });
                commands.push('Q');
            }
            commands.push(`0.12 0.12 0.12 rg BT /F2 ${pdfNumber(20 * scale)} Tf 1 0 0 1 ${pdfNumber(x(24))} ${pdfNumber(y(30))} Tm (${pdfText(layout.title)}) Tj ET`);

            const content = `${commands.join('\n')}\n`;
            const objects = [
                '<< /Type /Catalog /Pages 2 0 R >>',
                '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
                `<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${pdfNumber(pageWidth)} ${pdfNumber(pageHeight)}] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>`,
                `<< /Length ${content.length} >>\nstream\n${content}endstream`,
                '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
                '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            ];
            let pdf = '%PDF-1.4\n';
            const offsets = [0];
            objects.forEach((object, index) => {
                offsets.push(pdf.length);
                pdf += `${index + 1} 0 obj\n${object}\nendobj\n`;
            });
            const xrefOffset = pdf.length;
            pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
            offsets.slice(1).forEach(offset => { pdf += `${String(offset).padStart(10, '0')} 00000 n \n`; });
            pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`;
            return pdf;
        }

        async exportPng(layout) {
            const svgUrl = URL.createObjectURL(new Blob([this.toSvg(layout)], { type: 'image/svg+xml;charset=utf-8' }));
            try {
                const image = new Image();
                await new Promise((resolve, reject) => {
                    image.addEventListener('load', resolve, { once: true });
                    image.addEventListener('error', () => reject(new Error('Die PNG-Vorschau konnte nicht erzeugt werden.')), { once: true });
                    image.src = svgUrl;
                });
                const scale = Math.min(2, 16000 / layout.width, 16000 / layout.height);
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(layout.width * scale);
                canvas.height = Math.round(layout.height * scale);
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Der Browser unterstützt den PNG-Export nicht.');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                const png = await new Promise((resolve, reject) => canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Die PNG-Datei konnte nicht erzeugt werden.')), 'image/png'));
                this.downloadBlob(png, 'ad-organigramm.png');
            } finally {
                URL.revokeObjectURL(svgUrl);
            }
        }

        downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.append(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 0);
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.OrganizationExporter = OrganizationExporter;
})();
