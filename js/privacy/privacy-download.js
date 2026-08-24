(function() {
    'use strict';

    const pdfNumber = value => Number(value.toFixed(2)).toString();
    const pdfText = value => [...String(value ?? '')].map(character => {
        const code = character.codePointAt(0);
        if (character === '\\' || character === '(' || character === ')') return '\\' + character;
        if (code >= 32 && code <= 126) return character;
        const winAnsi = { 196: 196, 214: 214, 220: 220, 223: 223, 228: 228, 246: 246, 252: 252, 8364: 128, 8211: 150, 8212: 151, 8216: 145, 8217: 146, 8220: 147, 8221: 148, 8222: 132 }[code];
        return winAnsi ? '\\' + winAnsi.toString(8).padStart(3, '0') : '?';
    }).join('');

    function wrap(text, width = 92) {
        const words = String(text ?? '').replace(/\s+/g, ' ').trim().split(' ').filter(Boolean);
        const lines = [];
        let line = '';
        for (const word of words) {
            if (!line) { line = word; continue; }
            if ((line + ' ' + word).length <= width) { line += ' ' + word; continue; }
            lines.push(line); line = word;
        }
        if (line) lines.push(line);
        return lines.length ? lines : [''];
    }

    function sharedValue(items, field) {
        if (!items.length) return null;
        const first = String(items[0]?.[field] || '').trim();
        return first !== '' && items.every(item => String(item?.[field] || '').trim() === first) ? first : null;
    }

    function shortGermanDate(value) {
        const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}.${match[2]}.${match[1].slice(2)}` : String(value ?? '');
    }

    function reportLines(report) {
        const lines = [
            { text: 'Auskunft zu deinen personenbezogenen Daten', size: 18, bold: true, gap: 8 },
            { text: `Erstellt am ${shortGermanDate(report.article15.generatedAt)} für ${report.subject?.id || 'das angefragte Konto'}.`, size: 10, gap: 12 },
            { text: 'Deine Rechte', size: 14, bold: true, gap: 5 },
        ];
        for (const right of report.article15.rights || []) lines.push({ text: '- ' + right, size: 10, indent: 10 });
        lines.push({ text: report.article15.contactNote || '', size: 10, gap: 14 });
        if (report.coverage) {
            lines.push({ text: 'Umfang dieser Auskunft', size: 14, bold: true, gap: 5 });
            lines.push({ text: report.coverage.scopeNotice || '', size: 10, gap: 5 });
            lines.push({ text: 'Aktuell noch nicht implementierte Datenabrufe', size: 11, bold: true, gap: 3 });
            for (const source of report.coverage.notImplemented || []) lines.push({ text: '- ' + source, size: 9, indent: 10 });
            lines.push({ text: '', size: 9, gap: 10 });
        }

        for (const provider of report.providers || []) {
            lines.push({ text: provider.name || provider.appId, size: 15, bold: true, gap: 6, keepWithNext: true });
            const items = provider.items || [];
            if (!items.length) lines.push({ text: provider.status === 'failed' ? 'Diese App konnte für die Auskunft nicht erreicht werden.' : 'Für dich sind in dieser App keine Datensätze vorhanden.', size: 10, gap: 8 });
            const processing = provider.processing;
            if (processing) {
                lines.push({ text: 'Weitere Angaben zur Verarbeitung in dieser App', size: 10, bold: true, keepWithNext: true });
                lines.push({ text: 'Empfänger*innen: ' + (processing.recipients || []).join('; '), size: 9 });
                lines.push({ text: 'Herkunft: ' + String(processing.source || ''), size: 9 });
                lines.push({ text: 'Drittlandübermittlung: ' + String(processing.thirdCountryTransfers || ''), size: 9 });
                lines.push({ text: 'Automatisierte Entscheidungen: ' + String(processing.automatedDecisionMaking || ''), size: 9, gap: 12 });
            }
            const groups = new Map();
            for (const item of items) {
                const type = item.dataType || item.label;
                if (!groups.has(type)) groups.set(type, []);
                groups.get(type).push(item);
            }
            for (const [type, typeItems] of groups) {
                lines.push({ text: type, size: 11, bold: true, gap: 3, keepWithNext: true });
                const purpose = sharedValue(typeItems, 'purpose');
                const retention = sharedValue(typeItems, 'retention');
                if (purpose) lines.push({ text: 'Grund der Speicherung: ' + purpose, size: 9, gap: 2 });
                if (retention) lines.push({ text: 'Aufbewahrt bis: ' + retention, size: 9, gap: 5 });
                const fields = [];
                for (const item of typeItems) for (const field of Object.keys(item.attributes || {})) if (!fields.includes(field)) fields.push(field);
                const hasNote = typeItems.some(item => item.thirdPartyNote);
                const headers = [...fields, ...(hasNote ? ['Hinweis'] : []), ...(!purpose ? ['Grund der Speicherung'] : []), ...(!retention ? ['Aufbewahrt bis'] : [])];
                const rows = typeItems.map(item => {
                    const cells = fields.map(field => String(item.attributes?.[field] ?? '—'));
                    if (hasNote) cells.push(item.thirdPartyNote || '—');
                    if (!purpose) cells.push(item.purpose);
                    if (!retention) cells.push(item.retention);
                    return cells;
                });
                lines.push({ table: { headers, rows } });
            }
        }
        return lines;
    }

    function buildPdf(report) {
        if (!report?.article15?.generatedAt || !Array.isArray(report.providers) || !report.subject) throw new Error('Es liegt keine vollständige Auskunft zum Herunterladen vor.');
        const pageWidth = 595.28;
        const pageHeight = 841.89;
        const margin = 48;
        const pages = [];
        let commands = [];
        let y = pageHeight - margin;
        const finishPage = () => { pages.push(commands.join('\n') + '\n'); commands = []; y = pageHeight - margin; };
        for (const block of reportLines(report)) {
            if (block.table) {
                const columnCount = block.table.headers.length;
                const available = pageWidth - margin * 2;
                const widths = Array.from({ length: columnCount }, () => available / columnCount);
                const rowLayout = (cells, size) => cells.map((cell, index) => wrap(cell, Math.max(12, Math.floor((widths[index] - 8) / (size * 0.53)))));
                const rowHeight = (layout, size) => Math.max(...layout.map(cell => cell.length)) * size * 1.3 + 8;
                const drawRow = (cells, bold, header = false) => {
                    const size = bold ? 8 : 7.5;
                    const layout = rowLayout(cells, size);
                    const height = rowHeight(layout, size);
                    let x = margin;
                    for (let index = 0; index < cells.length; index++) {
                        commands.push(header ? '0.93 0.93 0.93 rg' : '1 1 1 rg');
                        commands.push(`0.55 0.55 0.55 RG ${pdfNumber(x)} ${pdfNumber(y - height)} ${pdfNumber(widths[index])} ${pdfNumber(height)} re B`);
                        layout[index].forEach((line, lineIndex) => {
                            const baseline = y - 5 - size - lineIndex * size * 1.3;
                            commands.push(`0.12 0.12 0.12 rg BT /${bold ? 'F2' : 'F1'} ${pdfNumber(size)} Tf 1 0 0 1 ${pdfNumber(x + 4)} ${pdfNumber(baseline)} Tm (${pdfText(line)}) Tj ET`);
                        });
                        x += widths[index];
                    }
                    y -= height;
                };
                const headerLayout = rowLayout(block.table.headers, 8);
                const headerHeight = rowHeight(headerLayout, 8);
                if (y - headerHeight < margin) finishPage();
                drawRow(block.table.headers, true, true);
                for (const cells of block.table.rows) {
                    const height = rowHeight(rowLayout(cells, 7.5), 7.5);
                    if (y - height < margin) {
                        finishPage();
                        drawRow(block.table.headers, true, true);
                    }
                    drawRow(cells, false);
                }
                y -= 10;
                continue;
            }
            const width = Math.max(38, Math.floor((pageWidth - margin * 2 - (block.indent || 0)) / (block.size * 0.53)));
            const wrapped = wrap(block.text, width);
            const lineHeight = block.size * 1.35;
            const required = wrapped.length * lineHeight + (block.gap || 0) + (block.keepWithNext ? 18 : 0);
            if (y - required < margin) finishPage();
            const font = block.bold ? 'F2' : block.italic ? 'F3' : 'F1';
            for (const line of wrapped) {
                commands.push(`0.12 0.12 0.12 rg BT /${font} ${pdfNumber(block.size)} Tf 1 0 0 1 ${pdfNumber(margin + (block.indent || 0))} ${pdfNumber(y)} Tm (${pdfText(line)}) Tj ET`);
                y -= lineHeight;
            }
            y -= block.gap || 0;
        }
        if (commands.length || pages.length === 0) finishPage();

        const objects = ['<< /Type /Catalog /Pages 2 0 R >>'];
        const pageIds = pages.map((_, index) => 3 + index * 2);
        objects.push(`<< /Type /Pages /Kids [${pageIds.map(id => `${id} 0 R`).join(' ')}] /Count ${pages.length} >>`);
        const fontStart = 3 + pages.length * 2;
        pages.forEach((content, index) => {
            const contentId = pageIds[index] + 1;
            objects.push(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${pageWidth} ${pageHeight}] /Resources << /Font << /F1 ${fontStart} 0 R /F2 ${fontStart + 1} 0 R /F3 ${fontStart + 2} 0 R >> >> /Contents ${contentId} 0 R >>`);
            objects.push(`<< /Length ${content.length} >>\nstream\n${content}endstream`);
        });
        objects.push('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        objects.push('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        objects.push('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>');

        let pdf = '%PDF-1.4\n';
        const offsets = [0];
        objects.forEach((object, index) => { offsets.push(pdf.length); pdf += `${index + 1} 0 obj\n${object}\nendobj\n`; });
        const xrefOffset = pdf.length;
        pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
        offsets.slice(1).forEach(offset => { pdf += `${String(offset).padStart(10, '0')} 00000 n \n`; });
        pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`;
        return pdf;
    }

    function download(report) {
        const pdf = buildPdf(report);
        const blob = new Blob([pdf], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'datenauskunft-' + shortGermanDate(report.article15.generatedAt) + '.pdf';
        document.body.append(link);
        link.click(); link.remove(); URL.revokeObjectURL(url);
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.privacy = window.LocalBase.privacy || {};
    window.LocalBase.privacy.PrivacyReportDownload = { buildPdf, download };
}());
