/**
 * Block Manager definitions — registers all drag & drop blocks with GrapesJS.
 * No custom drag/drop is implemented here; this only feeds GrapesJS's own
 * Block Manager.
 */
function registerBlocks(editor) {
    var bm = editor.BlockManager;

    function add(id, opts, category) {
        bm.add(id, Object.assign({ category: category }, opts));
    }

    // ---------- LAYOUT ----------
    add('section', {
        label: 'Section',
        content: '<section class="ppb-section" style="padding:60px 20px;"><div class="ppb-container" style="max-width:1140px;margin:0 auto;"></div></section>',
    }, 'Layout');

    add('container', {
        label: 'Container',
        content: '<div class="ppb-container" style="max-width:1140px;margin:0 auto;padding:0 15px;"></div>',
    }, 'Layout');

    add('row', {
        label: 'Row',
        content: '<div class="ppb-row" style="display:flex;flex-wrap:wrap;gap:20px;"></div>',
    }, 'Layout');

    add('column', {
        label: 'Column',
        content: '<div class="ppb-column" style="flex:1;min-width:0;padding:10px;"></div>',
    }, 'Layout');

    add('columns-2', {
        label: '2 Columns',
        content: '<div class="ppb-row" style="display:flex;flex-wrap:wrap;gap:20px;">' +
            '<div class="ppb-column" style="flex:1;min-width:200px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:200px;padding:10px;"></div>' +
            '</div>',
    }, 'Layout');

    add('columns-3', {
        label: '3 Columns',
        content: '<div class="ppb-row" style="display:flex;flex-wrap:wrap;gap:20px;">' +
            '<div class="ppb-column" style="flex:1;min-width:150px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:150px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:150px;padding:10px;"></div>' +
            '</div>',
    }, 'Layout');

    add('columns-4', {
        label: '4 Columns',
        content: '<div class="ppb-row" style="display:flex;flex-wrap:wrap;gap:20px;">' +
            '<div class="ppb-column" style="flex:1;min-width:120px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:120px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:120px;padding:10px;"></div>' +
            '<div class="ppb-column" style="flex:1;min-width:120px;padding:10px;"></div>' +
            '</div>',
    }, 'Layout');

    add('spacer', {
        label: 'Spacer',
        content: '<div class="ppb-spacer" style="height:40px;"></div>',
    }, 'Layout');

    // ---------- BASIC ----------
    add('heading', {
        label: 'Heading',
        content: '<h2 style="font-size:32px;font-weight:700;margin:0 0 10px;">Heading Text</h2>',
    }, 'Basic');

    add('text', {
        label: 'Text',
        content: '<div data-gjs-type="text" style="font-size:15px;line-height:1.6;">Insert your text here. Double click to edit.</div>',
    }, 'Basic');

    add('paragraph', {
        label: 'Paragraph',
        content: '<p style="font-size:15px;line-height:1.6;color:#4b5563;">This is a paragraph. Replace this with your own content.</p>',
    }, 'Basic');

    add('link', {
        label: 'Link',
        content: '<a href="#" style="color:#4f46e5;text-decoration:underline;">Link text</a>',
    }, 'Basic');

    add('button', {
        label: 'Button',
        content: '<a href="#" class="ppb-btn" style="display:inline-block;padding:12px 28px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">Click Me</a>',
    }, 'Basic');

    add('divider', {
        label: 'Divider',
        content: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">',
    }, 'Basic');

    add('icon', {
        label: 'Icon',
        content: '<div style="width:48px;height:48px;border-radius:50%;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:22px;">★</div>',
    }, 'Basic');

    // ---------- MEDIA ----------
    add('image', {
        label: 'Image',
        content: { type: 'image', style: { 'max-width': '100%' }, attributes: { src: '' } },
    }, 'Media');

    add('video', {
        label: 'Video',
        content: '<video controls style="width:100%;max-width:100%;"><source src="" type="video/mp4"></video>',
    }, 'Media');

    add('gallery', {
        label: 'Gallery',
        content: '<div class="ppb-gallery" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">' +
            '<div data-gjs-type="image" style="background:#d1d5db;height:120px;"></div>' +
            '<div data-gjs-type="image" style="background:#d1d5db;height:120px;"></div>' +
            '<div data-gjs-type="image" style="background:#d1d5db;height:120px;"></div>' +
            '</div>',
    }, 'Media');

    // ---------- CONTENT ----------
    add('hero', {
        label: 'Hero',
        content: '<section class="ppb-hero" style="padding:100px 20px;text-align:center;background:linear-gradient(135deg,#4f46e5,#1f2430);color:#fff;">' +
            '<div style="max-width:700px;margin:0 auto;">' +
            '<h1 style="font-size:44px;margin:0 0 16px;font-weight:800;">Build Your Website</h1>' +
            '<p style="font-size:18px;opacity:0.9;margin:0 0 28px;">Create beautiful pages visually with drag and drop.</p>' +
            '<a href="#" style="display:inline-block;padding:14px 32px;background:#fff;color:#4f46e5;border-radius:6px;text-decoration:none;font-weight:700;">Get Started</a>' +
            '</div></section>',
    }, 'Content');

    add('card', {
        label: 'Card',
        content: '<div class="ppb-card" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;max-width:320px;box-shadow:0 1px 4px rgba(0,0,0,.06);">' +
            '<div data-gjs-type="image" style="background:#d1d5db;height:160px;"></div>' +
            '<div style="padding:20px;">' +
            '<h3 style="margin:0 0 8px;font-size:18px;">Card Title</h3>' +
            '<p style="margin:0 0 16px;color:#6b7280;font-size:14px;">Short description text for this card goes here.</p>' +
            '<a href="#" style="display:inline-block;padding:9px 18px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;">Learn More</a>' +
            '</div></div>',
    }, 'Content');

    add('alert', {
        label: 'Alert',
        content: '<div style="padding:14px 18px;background:#fef3c7;color:#92400e;border-radius:8px;font-size:14px;">This is an alert message.</div>',
    }, 'Content');

    add('badge', {
        label: 'Badge',
        content: '<span style="display:inline-block;padding:4px 12px;background:#eef2ff;color:#4f46e5;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;">New</span>',
    }, 'Content');

    add('quote', {
        label: 'Quote',
        content: '<blockquote style="border-left:4px solid #4f46e5;margin:0;padding:10px 20px;font-style:italic;color:#374151;font-size:17px;">"This product changed the way we work."</blockquote>',
    }, 'Content');

    add('list', {
        label: 'List',
        content: '<ul style="font-size:15px;line-height:1.9;color:#374151;padding-left:20px;">' +
            '<li>First item</li><li>Second item</li><li>Third item</li></ul>',
    }, 'Content');

    add('pricing', {
        label: 'Pricing',
        content: '<div class="ppb-pricing" style="border:1px solid #e5e7eb;border-radius:10px;padding:32px 24px;text-align:center;max-width:280px;">' +
            '<h3 style="margin:0 0 6px;font-size:18px;">Pro Plan</h3>' +
            '<div style="font-size:36px;font-weight:800;margin:12px 0;">$29<span style="font-size:14px;font-weight:400;color:#6b7280;">/mo</span></div>' +
            '<ul style="list-style:none;padding:0;margin:0 0 24px;color:#4b5563;font-size:14px;line-height:2;">' +
            '<li>Unlimited pages</li><li>Priority support</li><li>Custom domain</li></ul>' +
            '<a href="#" style="display:block;padding:12px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">Choose Plan</a>' +
            '</div>',
    }, 'Content');

    add('feature', {
        label: 'Feature',
        content: '<div class="ppb-feature" style="text-align:center;max-width:260px;">' +
            '<div style="width:56px;height:56px;border-radius:50%;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px;">⚡</div>' +
            '<h4 style="margin:0 0 8px;font-size:16px;">Fast Performance</h4>' +
            '<p style="margin:0;color:#6b7280;font-size:14px;">Describe the feature benefit in a short sentence.</p>' +
            '</div>',
    }, 'Content');

    add('contact-section', {
        label: 'Contact',
        content: '<section style="padding:60px 20px;background:#f9fafb;">' +
            '<div style="max-width:600px;margin:0 auto;text-align:center;">' +
            '<h2 style="font-size:28px;margin:0 0 10px;">Get In Touch</h2>' +
            '<p style="color:#6b7280;margin:0 0 24px;">We would love to hear from you.</p>' +
            '<div style="display:flex;flex-direction:column;gap:12px;text-align:left;">' +
            '<input type="text" placeholder="Your name" style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;">' +
            '<input type="email" placeholder="Your email" style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;">' +
            '<textarea placeholder="Message" rows="4" style="padding:12px;border:1px solid #e5e7eb;border-radius:6px;font-family:inherit;"></textarea>' +
            '<a href="#" style="align-self:flex-start;padding:12px 28px;background:#4f46e5;color:#fff;border-radius:6px;text-decoration:none;font-weight:700;">Send Message</a>' +
            '</div></div></section>',
    }, 'Content');

    // ---------- ADVANCED ----------
    add('custom-html', {
        label: 'Custom HTML',
        content: '<div class="ppb-custom-html"><!-- Add your custom HTML here --></div>',
    }, 'Advanced');
}
