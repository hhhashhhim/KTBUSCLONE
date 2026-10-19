const assert = require('node:assert/strict');
const fs = require('node:fs');
const postcss = require('postcss');

const css = postcss.parse(fs.readFileSync('resources/js/pages/callCenter/callCenter.css', 'utf8'));
function declarations(selector, media = null) {
    const result = {};
    css.walkRules(selector, rule => {
        const context = rule.parent.type === 'atrule' ? rule.parent.params : null;
        if (context !== media) return;
        rule.walkDecls(decl => { result[decl.prop] = decl.value; });
    });
    return result;
}

const page = declarations('.cc-page');
assert.equal(page.padding, '24px 24px 36px', 'Desktop page has breathing room around the heading, tabs and cards');
assert.equal(page['box-sizing'], 'border-box', 'Padding stays inside the content width');
assert.equal(page.width, '100%');
assert.equal(declarations('.cc-page > .cc-heading')['margin-top'], '0', 'Override the legacy section negative heading margin');
assert.equal(declarations('.cc-workspace > *')['min-width'], '0', 'Wide child content must not expand the grid');
assert.equal(declarations('.cc-page', '(max-width: 600px)').padding, '16px 12px 24px', 'Keep gutters compact on mobile');
assert.equal(declarations('body.call-center-print .cc-page', 'print').padding, '0', 'Printed reports retain their original page margins');
console.log('PASS call center desktop/mobile gutters, heading alignment, grid containment and print spacing');
