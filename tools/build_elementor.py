"""Generate the Elementor JSON for the Bless My Socks homepage, UAE header and UAE footer.

Every element is a native Elementor container or widget (no HTML/shortcode widgets),
so the whole page stays editable in the Elementor panel.

Usage: python3 tools/build_elementor.py  -> writes elementor/{home,about,faq,contact,header,footer}.json
"""
import hashlib
import json
import os

SITE = 'https://blessmysocks.wsdfy.com'
UP = SITE + '/wp-content/uploads/2026/10/bms-'

# Media library IDs from the upload step (option bms_media_map).
MEDIA = {
    'bundle-1.png': 13, 'bundle-2.png': 14, 'bundle-3.png': 15,
    'cat-ev-blush.png': 16, 'cat-ev-cream.png': 17, 'cat-ev-lilac.png': 18, 'cat-ev-rose.png': 19,
    'cat-ev-sage.png': 20, 'cat-ev-tan.png': 21, 'cat-gf-box.png': 22, 'cat-gf-card.png': 23,
    'cat-gf-tee-rose.png': 24, 'cat-gf-tee-sage.png': 25, 'cat-gf-tee-white.png': 26, 'cat-gf-tote.png': 27,
    'cat-hero-everyday.png': 28, 'cat-hero-gifts.png': 29, 'cat-hero-statement.png': 30,
    'cat-st-bow.png': 31, 'cat-st-bows.png': 32, 'cat-st-cherry.png': 33, 'cat-st-daisy.png': 34,
    'cat-st-flower.png': 35, 'cat-st-hearts.png': 36, 'hero-poster.jpg': 37, 'logo-footer-white.png': 38,
    'logo-icon.png': 39, 'pastel-blush.png': 40, 'pastel-cream.png': 41, 'pastel-sage.png': 42,
    'sock-argyle.png': 43, 'sock-bee.png': 44, 'sock-daisy.png': 45, 'sock-dots.png': 46,
    'sock-koi.png': 47, 'sock-leopard.png': 48, 'hero-video.mp4': 49,
}

# WooCommerce product IDs (option bms_products).
PRODUCTS = {
    'rose-rib-crew': 52, 'cream-rib-crew': 53, 'sage-rib-crew': 54, 'lilac-rib-crew': 55,
    'oat-rib-crew': 56, 'blush-rib-crew': 57, 'hearts-crew': 58, 'daisy-crew': 59, 'bows-crew': 60,
    'flower-crew': 61, 'bow-rib-crew': 62, 'cherry-crew': 63, 'blush-frill-bow-crew': 64,
    'white-bow-tee': 65, 'rose-bow-tee': 66, 'sage-bow-tee': 67, 'gift-box': 68, 'tote-bag': 69,
    'greeting-card': 70,
}

INK, BLUE, MUTED, SOFT = '#1E222A', '#3D5A99', '#5F636B', '#8A8E95'
BG, LINE = '#F4F3F1', '#E4E1DC'

_counter = [0]


def rid():
    _counter[0] += 1
    return hashlib.md5(('bms%d' % _counter[0]).encode()).hexdigest()[:7]


def img(name, size=''):
    return {'url': UP + name, 'id': MEDIA[name], 'size': size, 'alt': '', 'source': 'library'}


def product_url(slug):
    return SITE + '/product/' + slug + '/'


def shop_url(rng):
    return SITE + '/shop/?cat=' + rng


def link(url, attrs=''):
    return {'url': url, 'is_external': '', 'nofollow': '', 'custom_attributes': attrs}


def px(v, unit='px'):
    return {'unit': unit, 'size': v, 'sizes': []}


def custom(v):
    return {'unit': 'custom', 'size': v, 'sizes': []}


def dims(t, r=None, b=None, l=None, unit='px'):
    r = t if r is None else r
    b = t if b is None else b
    l = r if l is None else l
    return {'unit': unit, 'top': str(t), 'right': str(r), 'bottom': str(b), 'left': str(l),
            'isLinked': len({str(t), str(r), str(b), str(l)}) == 1}


def gap(col, row=None):
    row = col if row is None else row
    return {'unit': 'px', 'size': col, 'column': str(col), 'row': str(row), 'isLinked': col == row}


def typo(prefix, size, weight='400', ls=None, lh=None, transform=None, style=None, ls_unit='em', lh_unit='em',
         size_tablet=None, size_mobile=None):
    p = prefix + '_' if prefix else ''
    s = {
        p + 'typography_typography': 'custom',
        p + 'typography_font_family': 'Jost',
        p + 'typography_font_size': custom(size) if isinstance(size, str) else px(size),
        p + 'typography_font_weight': str(weight),
    }
    if size_tablet is not None:
        s[p + 'typography_font_size_tablet'] = px(size_tablet)
    if size_mobile is not None:
        s[p + 'typography_font_size_mobile'] = px(size_mobile)
    if ls is not None:
        s[p + 'typography_letter_spacing'] = px(ls, ls_unit)
    if lh is not None:
        s[p + 'typography_line_height'] = px(lh, lh_unit)
    if transform:
        s[p + 'typography_text_transform'] = transform
    if style:
        s[p + 'typography_font_style'] = style
    return s


# ---------------------------------------------------------------- elements

def con(children, **s):
    base = {
        'content_width': 'full',
        'flex_direction': 'column',
        'flex_gap': gap(0),
        'padding': dims(0),
    }
    base.update(s)
    return {'id': rid(), 'elType': 'container', 'isInner': False, 'settings': base, 'elements': children}


def boxed(children, width=1180, pad_top=0, pad_bottom=0, **s):
    """A centred, max-width section matching the design's 1180px column with responsive gutters."""
    base = dict(
        content_width='boxed',
        boxed_width=px(width),
        padding=dims(pad_top, 40, pad_bottom, 40),
        padding_tablet=dims(pad_top and round(pad_top * .8), 24, pad_bottom and round(pad_bottom * .8), 24),
        padding_mobile=dims(pad_top and round(pad_top * .65), 16, pad_bottom and round(pad_bottom * .65), 16),
    )
    base.update(s)
    return con(children, **base)


def row(children, justify='space-between', align='center', g=12, wrap='wrap', **s):
    # Child containers in a row size to their content (Elementor defaults them to 100% width).
    for c in children:
        if c['elType'] == 'container':
            c['settings'].setdefault('width', custom('auto'))
            c['settings'].setdefault('width_mobile', custom('auto'))  # Elementor forces 100% on phones otherwise
    return con(children, flex_direction='row', flex_justify_content=justify, flex_align_items=align,
               flex_gap=gap(g), flex_wrap=wrap, **s)


def col(children, g=0, **s):
    return con(children, flex_direction='column', flex_gap=gap(g), **s)


def grid(children, cols, cols_tablet=None, cols_mobile=1, g=20, rg=None, **s):
    base = dict(
        container_type='grid',
        grid_columns_grid=px(cols, 'fr') if isinstance(cols, int) else custom(cols),
        grid_columns_grid_tablet=px(cols_tablet or cols, 'fr') if isinstance(cols_tablet or cols, int) else custom(cols_tablet),
        grid_columns_grid_mobile=px(cols_mobile, 'fr') if isinstance(cols_mobile, int) else custom(cols_mobile),
        grid_rows_grid=px(1, 'fr'),
        grid_gaps={'unit': 'px', 'column': str(g), 'row': str(g if rg is None else rg), 'isLinked': rg is None},
        grid_auto_flow='row',
    )
    base.update(s)
    return con(children, **base)


def widget(kind, **s):
    return {'id': rid(), 'elType': 'widget', 'widgetType': kind, 'settings': s, 'elements': []}


def heading(text, size, tag='div', weight='400', color=INK, align=None, url=None, **kw):
    t = {k: kw.pop(k) for k in list(kw) if k in ('ls', 'lh', 'transform', 'style', 'ls_unit', 'lh_unit', 'size_tablet', 'size_mobile')}
    s = dict(title=text, header_size=tag, title_color=color, **typo('', size, weight, **t))
    if align:
        s['align'] = align
    if url:
        s['link'] = link(url)
    s.update(kw)
    return widget('heading', **s)


def eyebrow(text, color=BLUE, size=11, ls=0.28, **kw):
    return heading(text, size, 'span', '500', color, ls=ls, transform='uppercase', **kw)


def h2(text, size='clamp(32px,3.6vw,44px)', lh=1.0, color=INK, **kw):
    return heading(text, size, 'h2', '300', color, ls=-0.01, lh=lh, **kw)


def para(html, size=14, color=MUTED, lh=1.6, maxw=None, align=None, **kw):
    s = dict(editor='<p>' + html + '</p>', text_color=color, **typo('', size, '400', lh=lh))
    if maxw:
        s['_element_width'] = 'initial'
        s['_element_custom_width'] = px(maxw)
    if align:
        s['align'] = align
    s.update(kw)
    return widget('text-editor', **s)


def button(text, url, kind='dark', size=14, pad=(14, 26), classes='', weight='500', **kw):
    s = dict(text=text, link=link(url), size='sm', **typo('', size, weight))
    s['border_radius'] = dims(999)
    s['text_padding'] = dims(pad[0], pad[1])
    s['button_hover_transition_duration'] = px(0, 's')  # design swaps hover colours instantly
    if kind == 'dark':
        s.update(background_background='classic', background_color=INK, button_text_color='#FFFFFF',
                 button_background_hover_background='classic', button_background_hover_color=BLUE, hover_color='#FFFFFF')
    elif kind == 'blue':
        s.update(background_background='classic', background_color=BLUE, button_text_color='#FFFFFF',
                 button_background_hover_background='classic', button_background_hover_color='#4D6BAD', hover_color='#FFFFFF')
    elif kind == 'outline':
        s.update(background_background='classic', background_color='rgba(255,255,255,0.6)', button_text_color=INK,
                 border_border='solid', border_width=dims(1), border_color=INK,
                 button_background_hover_background='classic', button_background_hover_color='#FFFFFF', hover_color=INK,
                 button_hover_border_color=INK)
    elif kind == 'ghost':
        s.update(background_background='classic', background_color='rgba(0,0,0,0)', button_text_color=INK,
                 border_border='solid', border_width=dims(1), border_color='#D6D2CB',
                 button_background_hover_background='classic', button_background_hover_color=INK, hover_color='#FFFFFF',
                 button_hover_border_color=INK)
    elif kind == 'arrow':
        s.update(background_background='classic', background_color='#FFFFFF', button_text_color=INK,
                 border_border='solid', border_width=dims(1), border_color=LINE,
                 button_background_hover_background='classic', button_background_hover_color=INK, hover_color='#FFFFFF')
    elif kind == 'tab':
        s.update(background_background='classic', background_color='rgba(0,0,0,0)', button_text_color=INK,
                 button_background_hover_background='classic', button_background_hover_color='#FFFFFF', hover_color=INK)
    if classes:
        s['_css_classes'] = classes
    s.update(kw)
    return widget('button', **s)


def image(name, size='large', url=None, classes='', radius=None, **kw):
    s = dict(image=img(name), image_size=size, width=px(100, '%'))
    if url:
        s.update(link_to='custom', link=link(url))
    if classes:
        s['_css_classes'] = classes
    if radius is not None:
        s['image_border_radius'] = dims(radius)
    s.update(kw)
    return widget('image', **s)


def pill(text, bg, fg):
    # Phones: smaller text and side padding so both bundle pills sit side by side down to 320px.
    return heading(text, 12, 'span', '400', fg, size_mobile=11, _background_background='classic', _background_color=bg,
                   _padding=dims(8, 14), _padding_mobile=dims(7, 10), _border_radius=dims(999), _css_classes='bms-nowrap')


def mark_inner(el, depth=0):
    if el['elType'] == 'container':
        el['isInner'] = depth > 0
        for c in el['elements']:
            mark_inner(c, depth + 1)
    return el


SECTION_PAD = 112  # clamp(72px, 9vw, 112px) in the design


# ---------------------------------------------------------------- homepage

def hero():
    socks = [('sock-daisy.png', ''), ('sock-leopard.png', ' bms-sock-flip'), ('sock-bee.png', ''),
             ('sock-argyle.png', ' bms-sock-flip'), ('sock-koi.png', ''), ('sock-dots.png', ' bms-sock-flip')]
    line = con([image(n, 'medium', classes='bms-sock bms-sock-%d%s' % (i + 1, flip)) for i, (n, flip) in enumerate(socks)],
               css_classes='bms-line')
    content = col([
        eyebrow('Christian socks &amp; tees', align='center'),
        heading('Walk in<br><em style="color:#3D5A99">faith.</em>', 'clamp(36px,4.4vw,58px)', 'h1', '300', INK,
                align='center', ls=-0.02, lh=0.98),
        para('Soft socks and tees that carry a quiet word of faith, every step of the day.', 15, '#3A3F48', 1.5, maxw=440,
             align='center', typography_font_size_mobile=px(14)),
        row([button('Shop bestsellers', '#shop'), button('Our story →', '#about', 'outline')], 'center', g=12),
        heading('A$20 a pair · 3+ pairs 20% off · 5+ pairs free shipping', 12, 'span', '400', MUTED, align='center',
                ls=0.04, size_mobile=11, typography_letter_spacing_mobile=px(0, 'em'),
                _css_classes='bms-hero-note'),  # one line on phones; bms.css drops it to 10px under 360px
    ], g=12, flex_gap_mobile=gap(10), flex_justify_content='flex-end', flex_align_items='center',
        padding=dims(24, 24, 28, 24), padding_mobile=dims(16, 16, 22, 16), css_classes='bms-hero-content')
    # Client comment: the cloud area at half its former height, framed as a rounded panel inside the same
    # 1180px column (8px corners) as the sock photo boxes below. Panel height is set in bms.css (.bms-hero).
    panel = con([line, content], css_classes='bms-hero', position='relative', overflow='hidden',
                flex_justify_content='flex-end', min_height=px(380), border_radius=dims(8),
                background_background='video', background_video_link=UP + 'hero-video.mp4',
                background_video_fallback=img('hero-poster.jpg'))
    # Same 1320px column as the header, so the panel's edges line up with the logo and the Cart pill.
    return boxed([panel], width=1320, pad_top=20, padding_tablet=dims(16, 24, 0, 24), padding_mobile=dims(12, 16, 0, 16))


def collection_card(eyebrow_text, accent, title, sub, hero_img, cat, items, price_from):
    strip = con([widget('image-box', image=img(f), thumbnail_size='medium', title_text=name, description_text='',
                        link=link(product_url(slug)), title_size='span', text_align='center',
                        image_space=px(14), image_size=px(100, '%'), title_color=MUTED,
                        **typo('title', 11, '400'), _css_classes='bms-strip-item')
                 for f, name, slug in items],
                flex_direction='row', flex_gap=gap(8), flex_wrap='nowrap', padding=dims(18, 26),
                padding_tablet=dims(14, 18), padding_mobile=dims(18, 26), css_classes='bms-strip')
    return col([
        # Tablets fit three cards per row: tighter padding and a 22px title (phones keep the desktop values).
        col([eyebrow(eyebrow_text, accent, 10, 0.26),
             heading(title, 28, 'h3', '300', INK, lh=1.1, size_tablet=22, size_mobile=28),
             heading(sub, 13, 'span', '400', MUTED, size_tablet=12, size_mobile=13)], g=8, padding=dims(26, 26, 0, 26),
            padding_tablet=dims(20, 18, 0, 18), padding_mobile=dims(26, 26, 0, 26)),
        image(hero_img, 'medium_large', shop_url(cat), 'bms-ar-43', 6, _margin=dims(22, 26, 0, 26),
              _margin_tablet=dims(16, 18, 0, 18), _margin_mobile=dims(22, 26, 0, 26)),
        con([strip,
             button('←', '#', 'arrow', 13, (0, 0), 'bms-arrow bms-prev', '400'),
             button('→', '#', 'arrow', 13, (0, 0), 'bms-arrow bms-next', '400')],
            css_classes='bms-strip-wrap', position='relative'),
        row([heading(price_from, 12, 'span', '400', MUTED), heading('EXPLORE →', 12, 'span', '400', INK, ls=0.18)],
            g=12, html_tag='a', link=link(shop_url(cat)), padding=dims(18, 26), padding_tablet=dims(16, 18),
            padding_mobile=dims(18, 26), border_border='solid',
            border_width=dims(1, 0, 0, 0), border_color='#E4E0D9', background_background='classic',
            background_color='rgba(255,255,255,0.35)', css_classes='bms-card-foot'),
    ], background_background='classic', background_color='#F1EFEA', border_border='solid', border_width=dims(1),
        border_color='#E4E0D9', border_radius=dims(8), overflow='hidden', css_classes='bms-lift')


def collections():
    ev = [('cat-ev-rose.png', 'Rose', 'rose-rib-crew'), ('cat-ev-cream.png', 'Cream', 'cream-rib-crew'),
          ('cat-ev-sage.png', 'Sage', 'sage-rib-crew'), ('cat-ev-lilac.png', 'Lilac', 'lilac-rib-crew'),
          ('cat-ev-tan.png', 'Oat', 'oat-rib-crew'), ('cat-ev-blush.png', 'Blush', 'blush-frill-bow-crew')]
    st = [('cat-st-hearts.png', 'Hearts', 'hearts-crew'), ('cat-st-daisy.png', 'Daisy', 'daisy-crew'),
          ('cat-st-bows.png', 'Bows', 'bows-crew'), ('cat-st-flower.png', 'Flower', 'flower-crew'),
          ('cat-st-cherry.png', 'Cherry', 'cherry-crew')]
    gf = [('cat-gf-tee-white.png', 'White Tee', 'white-bow-tee'), ('cat-gf-tee-rose.png', 'Rose Tee', 'rose-bow-tee'),
          ('cat-gf-tee-sage.png', 'Sage Tee', 'sage-bow-tee'), ('cat-gf-box.png', 'Gift Box', 'gift-box'),
          ('cat-gf-tote.png', 'Tote', 'tote-bag'), ('cat-gf-card.png', 'Card', 'greeting-card')]
    return boxed([
        row([col([eyebrow('Shop by range'), h2('The collections')], g=12),
             para('Each made to carry a blessing. Tap one to see every style and colour.', 14, MUTED, 1.55, maxw=300)],
            align='flex-end', g=24),
        grid([
            collection_card('Comfort for every day', '#8A5A4E', 'Everyday Socks', 'Soft ribbed cotton in gentle colours.',
                            'cat-hero-everyday.png', 'socks', ev, 'From A$20.00'),
            collection_card('Fun details', '#5E7250', 'Statement Socks', 'Frilled cuffs, little prints, big joy.',
                            'cat-hero-statement.png', 'socks', st, 'From A$22.00'),
            collection_card('Thoughtful picks', BLUE, 'Gifts &amp; Tees', 'For church, family and friends.',
                            'cat-hero-gifts.png', 'gifts', gf, 'From A$6.00'),
        ], 3, 3, 1, 20, grid_align_items='start', grid_align_items_tablet='stretch'),
    ], pad_top=SECTION_PAD, flex_gap=gap(32))


def product_card(slug, name, price, image_name, dots=(), selected=-1, sub=''):
    dots_html = ''.join('<span class="bms-dot%s" style="background:%s"></span>' % (' is-selected' if i == selected else '', c)
                        for i, c in enumerate(dots))
    if sub:
        dots_html += '<span style="font-size:11px;color:#8A8E95">%s</span>' % sub
    return col([
        con([image(image_name, 'medium_large', product_url(slug), 'bms-ar-45 bms-zoom'),
             heading('BESTSELLER', 8, 'span', '500', '#FFFFFF', ls=0.16, _background_background='classic',
                     _background_color=INK, _padding=dims(4, 9), _border_radius=dims(999), _position='absolute',
                     _offset_x=px(10), _offset_y=px(10), _z_index=2, _element_width='auto')],
            position='relative', border_radius=dims(6), overflow='hidden', background_background='classic',
            background_color='#EDEFE8'),
        row([heading(name, 13, 'span', '400', INK, url=product_url(slug)),
             heading(price, 12, 'span', '400', INK, _css_classes='bms-num')], g=12, align='baseline', wrap='nowrap'),
        row([widget('text-editor', editor='<p>' + dots_html + '</p>', **typo('', 11, '400', lh=1),
                    _css_classes='bms-dots'),
             button('Add +', SITE + '/?add-to-cart=%d' % PRODUCTS[slug], 'ghost', 11, (5, 12), 'bms-add', '400',
                    typography_letter_spacing=px(0.06, 'em'))], g=12, wrap='nowrap', min_height=px(22)),
    ], g=10)


def bestsellers():
    palette = ['#F1E9DA', '#D9877F', '#8C9F78', '#B9A6D3', '#E3B08E']
    return boxed([
        row([col([eyebrow('Most loved'), h2('Start with a bestseller')], g=12),
             heading('ALL COLLECTIONS ↓', 10, 'span', '400', INK, ls=0.16, url=SITE + '/shop/')],
            align='flex-end', g=16),
        grid([
            product_card('cream-rib-crew', 'Cream Rib Crew', 'A$20.00', 'pastel-cream.png', palette, 0),
            product_card('sage-rib-crew', 'Sage Rib Crew', 'A$20.00', 'pastel-sage.png', palette, 2),
            product_card('blush-frill-bow-crew', 'Blush Frill Bow Crew', 'A$22.00', 'pastel-blush.png', sub='New'),
        ], 3, 3, 1, 14),
    ], pad_top=SECTION_PAD, flex_gap=gap(20), _element_id='shop')


GALLERY = [('ev-rose', 'Rose'), ('st-hearts', 'Hearts'), ('gf-tee-white', 'White Tee'), ('ev-cream', 'Cream'),
           ('st-daisy', 'Daisy'), ('gf-box', 'Gift Box'), ('ev-sage', 'Sage'), ('st-bows', 'Bows'),
           ('gf-tote', 'Tote'), ('ev-lilac', 'Lilac'), ('st-flower', 'Flower'), ('gf-tee-rose', 'Rose Tee'),
           ('ev-tan', 'Oat'), ('st-bow', 'Bow Rib'), ('gf-card', 'Card'), ('ev-blush', 'Blush'),
           ('st-cherry', 'Cherry'), ('gf-tee-sage', 'Sage Tee')]


def gallery_item(key, name, group):
    f = 'cat-%s.png' % key
    attrs = 'data-elementor-open-lightbox|yes,data-elementor-lightbox-slideshow|%s,data-elementor-lightbox-title|%s' % (group, name)
    return image(f, 'medium', UP + f, 'bms-gal', 6, _element_width='initial', _element_custom_width=custom('clamp(96px,12.5vw,168px)'),
                 link=link(UP + f, attrs))


def gallery():
    # Each marquee row repeats its nine photos so the loop is seamless. The first copies form one lightbox
    # slideshow and the repeats another, so either copy opens a full 18-photo slideshow.
    top = [gallery_item(k, n, 'bms-gallery-a') for k, n in GALLERY[:9]] + [gallery_item(k, n, 'bms-gallery-b') for k, n in GALLERY[:9]]
    bottom = [gallery_item(k, n, 'bms-gallery-a') for k, n in GALLERY[9:]] + [gallery_item(k, n, 'bms-gallery-b') for k, n in GALLERY[9:]]

    def track(items, cls):
        return con([con(items, flex_direction='row', flex_gap=gap(10), flex_wrap='nowrap', padding=dims(0, 10, 0, 0),
                        css_classes=cls)], overflow='hidden', padding=dims(8, 0))

    return con([
        col([eyebrow('From the studio', size=10), h2('Every pair, a blessing.', align='center'),
             heading('Tap any photo to see the detail', 12, 'span', '400', SOFT, align='center')],
            g=10, flex_align_items='center', padding=dims(0, 16)),
        col([track(top, 'bms-track-r'), track(bottom, 'bms-track')], g=10),
    ], g=32, flex_align_items='center', padding=dims(SECTION_PAD, 0, 0, 0), padding_tablet=dims(90, 0, 0, 0),
        padding_mobile=dims(72, 0, 0, 0))


def bundle():
    return boxed([
        grid([
            col([eyebrow('Mix · Match · Bless'),
                 h2('Build a gift bundle.', lh=1.05),
                 para('Pick any three or more pairs and tees across every range. Three or more pairs save 20%; five or '
                      'more also ship free. The saving is applied at checkout.', 15, '#3A3F48', 1.65, _element_width_tablet='inherit', maxw=440),
                 row([pill('3+ pairs · 20% off', '#FFFFFF', INK), pill('5+ pairs · free shipping', INK, '#FFFFFF')],
                     'flex-start', g=8, wrap='nowrap', flex_gap_mobile=gap(6)),
                 button('Start building →', SITE + '/shop/')],
                g=18, flex_justify_content='center', flex_align_items='flex-start', padding=dims(64),
                padding_tablet=dims(40), padding_mobile=dims(20)),
            grid([image('bundle-1.png', 'medium_large', classes='bms-fill', radius=4),
                  image('bundle-2.png', 'medium_large', classes='bms-fill', radius=4),
                  image('bundle-3.png', 'medium_large', classes='bms-fill', radius=4)], 3, 3, 3, 6,
                 padding=dims(6), min_height=px(320), min_height_mobile=px(0), css_classes='bms-bundle-media'),
        ], 2, 1, 1, 0, background_background='classic', background_color='#E6EAF1', border_radius=dims(6),
            overflow='hidden'),
    ], pad_top=SECTION_PAD)


def step(num, title, text):
    return col([heading(num, 12, 'span', '400', BLUE), heading(title, 16, 'span', '400', INK),
                heading(text, 13, 'span', '400', MUTED, lh=1.6)],
               g=8, border_border='solid', border_width=dims(1, 0, 0, 0), border_color=INK, padding=dims(16, 0, 0, 0))


def region_rows(region, rows):
    return col([row([heading(p, 14, 'span', '400', INK), heading(d + ' days', 14, 'span', '400', BLUE, _css_classes='bms-num')],
                    g=16, wrap='nowrap', padding=dims(14, 0), border_border='solid', border_width=dims(1, 0, 0, 0),
                    border_color='#ECE9E4', css_classes='bms-region-row') for p, d in rows], css_classes='bms-region-' + region)


def return_card(label, color, title, text, bg):
    return col([eyebrow(label, color, 10, 0.26), heading(title, 18, 'span', '400', INK),
                heading(text, 13, 'span', '400', MUTED, lh=1.6)],
               g=8, padding=dims(24), background_background='classic', background_color=bg, border_radius=dims(6))


def shipping():
    return boxed([
        grid([
            col([eyebrow('Shipping &amp; returns'), h2('Packed with care, sent within a day.', lh=1.05),
                 para('Every order is packed by hand and lodged with Australia Post within one business day, with tracking '
                      'door to door.', 14, MUTED, 1.65, _element_width_tablet='inherit', maxw=420)], g=12),
            grid([step('01', 'Dispatch', 'Within one business day, Monday to Friday.'),
                  step('02', 'Tracking', 'A tracking link is emailed as soon as your parcel is scanned.')], 2, 2, 1, 24,
                 grid_align_items='start'),
        ], 2, 1, 1, 64, rg=28),
        col([
            row([col([heading('Estimated delivery', 18, 'span', '400', INK),
                      heading('Business days after dispatch · A$7.99 · free on 5+ pairs', 12, 'span', '400', MUTED,
                              _css_classes='bms-region-au'),
                      heading('Business days after dispatch · A$12.99 · free on 5+ pairs', 12, 'span', '400', MUTED,
                              _css_classes='bms-region-intl')], g=4),
                 row([button('Australia', '#au', 'tab', 13, (8, 16), 'bms-tab is-active', '400'),
                      button('International', '#intl', 'tab', 13, (8, 16), 'bms-tab', '400')], 'flex-start', g=4,
                     wrap='nowrap', padding=dims(4), background_background='classic', background_color=BG,
                     border_radius=dims(999))], g=16),
            col([region_rows('au', [('South-East Queensland', '2–3'), ('Sydney, Melbourne, Canberra, Adelaide', '3–6'),
                                    ('Perth, Darwin, Hobart, regional &amp; remote', '5–10')]),
                 region_rows('intl', [('New Zealand', '5–8'), ('United States &amp; Canada', '6–10'),
                                      ('United Kingdom &amp; Europe', '7–12')])]),
        ], g=20, padding=dims(32), padding_mobile=dims(20), background_background='classic', background_color='#FFFFFF',
            border_border='solid', border_width=dims(1), border_color=LINE, border_radius=dims(6)),
        col([
            row([heading('Returns &amp; exchanges', 22, 'span', '300', INK),
                 heading('Our 30-day promise is in addition to your Australian Consumer Law rights.', 12, 'span', '400', MUTED)],
                align='baseline', g=16),
            grid([return_card('Faulty pair', '#8A5A4E', 'Free replacement',
                              'Email us within 30 days with your order number and a photo, and we’ll send a new pair at no cost.',
                              '#ECE9E4'),
                  return_card('Change of mind', '#5E7250', 'Exchange unworn items',
                              'Swap for another colour or design within 30 days. You cover return postage; we cover the replacement.',
                              '#E8EAE3'),
                  return_card('Worn socks', BLUE, 'Hygiene restriction',
                              'Worn socks can’t be returned for change of mind. Faulty goods are always covered.', '#E6EAF1')],
                 3, 3, 1, 16),  # tablets keep all three cards in one row
        ], g=16, _element_id='returns'),
    ], pad_top=SECTION_PAD, flex_gap=gap(40), _element_id='shipping')


def story():
    return con([
        boxed([
            col([eyebrow('Our story', '#A9B8D8'),
                 h2('Faith you can <em style="color:#A9B8D8">walk in.</em>', 'clamp(34px,4vw,52px)', 1.05, '#FFFFFF'),
                 para('Bless My Socks began with a simple idea: everyday clothes that remind us we’re loved. Laura’s words go here.',
                      15, '#C9CCD2', 1.65, _element_width_tablet='inherit', maxw=440),
                 button('Read our story →', SITE + '/about/', 'blue', 13, (12, 22))], g=20, flex_align_items='flex-start'),
            grid([image('pastel-cream.png', 'medium_large', classes='bms-fill'),
                  image('bundle-1.png', 'medium_large', classes='bms-fill')], 2, 2, 2, 0,
                 border_radius=dims(6), overflow='hidden', background_background='classic', background_color='#EDEFE8',
                 css_classes='bms-story-media', min_height=px(420), min_height_tablet=px(380), min_height_mobile=px(260)),
        ], pad_top=96, pad_bottom=96, container_type='grid', grid_columns_grid=px(2, 'fr'),
            grid_columns_grid_tablet=px(1, 'fr'), grid_columns_grid_mobile=px(1, 'fr'), grid_rows_grid=px(1, 'fr'),
            grid_gaps={'unit': 'px', 'column': '64', 'row': '40', 'isLinked': False}, grid_align_items='center'),
    ], background_background='classic', background_color=INK, margin=dims(SECTION_PAD, 0, 0, 0),
        margin_tablet=dims(90, 0, 0, 0), margin_mobile=dims(72, 0, 0, 0), _element_id='about')


def values():
    def cell(t, d):
        return col([heading(t, 15, 'span', '400', INK), heading(d, 13, 'span', '400', MUTED, lh=1.6)], g=8, padding=dims(28))

    return con([
        boxed([
            col([h2('Soft on your feet.<br>Strong in faith.', lh=1.1, align='center'),
                 para('Made for everyday wear, with a word of hope in every pair.', 14, MUTED, 1.6, maxw=460, align='center')],
                g=16, flex_align_items='center'),
            grid([cell('Soft cotton blend', 'Breathable and comfortable from morning to night.'),
                  cell('A quiet message', 'Scripture and blessings knitted into every pair.'),
                  cell('Made to share', 'A thoughtful gift for church, family and friends.')], 3, 3, 1, 0,
                 border_border='solid', border_width=dims(1), border_color='#D9D5CE', border_radius=dims(6),
                 css_classes='bms-values'),
        ], width=1000, pad_top=104, pad_bottom=104, flex_gap=gap(40), flex_align_items='stretch'),
    ], background_background='classic', background_color='#ECEAE6')


def marquee():
    items = []
    for _ in range(10):
        items.append(heading('BLESS MY SOCKS<sup class="bms-reg">®</sup>', 'clamp(40px,6vw,72px)', 'span', '300',
                             '#D3D0CA', ls=0.12))
        items.append(heading('—', 'clamp(40px,6vw,72px)', 'span', '300', BLUE))
    return con([con(items, flex_direction='row', flex_gap=gap(48), flex_wrap='nowrap', flex_align_items='center',
                    css_classes='bms-track bms-marquee-text')],
               overflow='hidden', background_background='classic', background_color=BG, padding=dims(20, 0))


def home():
    return [mark_inner(s) for s in (hero(), collections(), bestsellers(), gallery(), bundle(), shipping(), story(),
                                    values(), marquee())]


# ---------------------------------------------------------------- about page

def about_split(eyebrow_text, title, text, image_name, image_first, **extra):
    """Story / craft rows: a 4:5 photo beside a short text block (stacked on phones, text first for the craft row)."""
    text_col = col([eyebrow(eyebrow_text, size=10), h2(title, 'clamp(30px,3.4vw,42px)', 1.1),
                    para(text, 14, MUTED, 1.75)], g=18, width=px(420), width_tablet=custom('100%'),
                    width_mobile=custom('100%'), css_classes='bms-about-text')
    photo = image(image_name, 'medium_large', classes='bms-ar-45', radius=10)
    return boxed([photo, text_col] if image_first else [text_col, photo], width=1080, pad_top=112,
                 container_type='grid', grid_columns_grid=px(2, 'fr'), grid_columns_grid_tablet=px(2, 'fr'),
                 grid_columns_grid_mobile=px(1, 'fr'), grid_rows_grid=px(1, 'fr'),
                 grid_gaps={'unit': 'px', 'column': '88', 'row': '32', 'isLinked': False},
                 grid_gaps_tablet={'unit': 'px', 'column': '48', 'row': '32', 'isLinked': False},
                 grid_gaps_mobile={'unit': 'px', 'column': '0', 'row': '32', 'isLinked': False},
                 grid_align_items='center', **extra)


def about_hero():
    content = col([
        eyebrow('About us', align='center'),
        heading('Faith you can<br><em style="color:#3D5A99">walk in.</em>', 'clamp(44px,6vw,80px)', 'h1', '300', INK,
                align='center', ls=-0.02, lh=1),
    ], g=18, flex_justify_content='flex-end', flex_align_items='center', padding=dims(24, 24, 64, 24),
        padding_tablet=dims(24, 24, 48, 24), padding_mobile=dims(24, 24, 40, 24), css_classes='bms-about-hero-content')
    # Height is set in bms.css (.bms-about-hero): clamp(420px, 62vh, 600px) as in the design.
    return con([content], css_classes='bms-about-hero', position='relative', overflow='hidden', min_height=px(420),
               min_height_mobile=px(0),
               flex_justify_content='flex-end', background_background='video', background_video_link=UP + 'hero-video.mp4',
               background_video_fallback=img('hero-poster.jpg'), background_play_on_mobile='yes',
               # The design's fade into the page colour across the lower half, as an editable background overlay.
               background_overlay_background='gradient', background_overlay_gradient_type='linear', background_overlay_color='rgba(244,243,241,0)',
               background_overlay_color_stop=px(45, '%'), background_overlay_color_b=BG,
               background_overlay_color_b_stop=px(100, '%'), background_overlay_gradient_angle=px(180, 'deg'),
               background_overlay_opacity=px(1, ''))  # Elementor defaults overlays to 50% opacity


def about_values():
    def cell(icon, title, text):
        return col([widget('icon', selected_icon=icon, primary_color='#9AA0A8', size=px(18), align='left'),
                    heading(title, 14, 'span', '400', INK), heading(text, 12, 'span', '400', MUTED, lh=1.65)],
                   g=10, padding=dims(26))

    return con([
        boxed([
            col([eyebrow('The details that matter', size=10, align='center'),
                 h2('Comfort in every step.', 'clamp(32px,3.6vw,44px)', 1.08, align='center'),
                 para('Soft cotton, a gentle message and a fit made for everyday wear.', 13, MUTED, 1.65, maxw=420,
                      align='center')], g=14, flex_align_items='center'),
            grid([cell({'value': 'far fa-heart', 'library': 'fa-regular'}, 'Made with care',
                       'Every pair is checked by hand before it’s packed and sent.'),
                  cell({'value': 'fas fa-grip-lines-vertical', 'library': 'fa-solid'}, 'Soft cotton blend',
                       'Breathable and comfortable from morning to night.'),
                  cell({'value': 'fas fa-cross', 'library': 'fa-solid'}, 'A quiet message',
                       'Scripture and blessings knitted into every pair.')], 3, 3, 1, 0,
                 background_background='classic', background_color=BG, border_border='solid', border_width=dims(1),
                 border_color='#D9D5CE', border_radius=dims(10), css_classes='bms-values'),
        ], width=1080, pad_top=96, pad_bottom=96, flex_gap=gap(40), flex_align_items='stretch'),
    ], background_background='classic', background_color='#ECEAE6', margin=dims(120, 0, 0, 0),
        margin_tablet=dims(90, 0, 0, 0), margin_mobile=dims(72, 0, 0, 0))


def about_verse():
    return con([
        boxed([
            eyebrow('Psalm 119:105', '#A9B8D8', align='center'),
            heading('“Thy word is a lamp unto my feet, and a <em style="color:#A9B8D8">light unto my path.</em>”',
                    'clamp(28px,3.6vw,44px)', 'p', '300', '#FFFFFF', align='center', ls=-0.01, lh=1.25,
                    _css_classes='bms-balance'),
        ], width=880, pad_top=104, pad_bottom=104, flex_gap=gap(22), flex_align_items='center'),
    ], background_background='classic', background_color=INK)


def about_cta():
    return boxed([
        h2('Find your pair.', 'clamp(32px,3.6vw,44px)', 1.05, align='center'),
        row([button('Shop the range', SITE + '/shop/'), button('Get in touch', SITE + '/contact/', 'outline')], 'center', g=10),
    ], pad_top=112, pad_bottom=112, flex_gap=gap(20), flex_align_items='center')


def about():
    return [mark_inner(s) for s in (
        about_hero(),
        about_split('Our story', 'Made for comfort.<br>Blessed with purpose.',
                    'Bless My Socks began with a wish to make everyday clothes that remind us we’re loved. Laura’s story '
                    'goes here: where the idea came from and what she hopes each pair brings.', 'pastel-cream.png', True,
                    # Phones: the shorter hero already ends in a soft fade, so the story starts closer beneath it.
                    padding_mobile=dims(40, 16, 0, 16)),
        about_split('How we make them', 'Knitted, not printed.',
                    'Every message is knitted into the sock, so it lasts wash after wash. Soft ribbed cuffs, reinforced '
                    'heels and toes, and every pair checked before it leaves us.', 'pastel-blush.png', False),
        about_values(), about_verse(), about_cta())]


# ---------------------------------------------------------------- FAQ (support) page

LINE2, BODY2 = '#D9D5CE', '#3A3F48'


def micro(text, color=MUTED, ls=0.2, **kw):
    """The design's 9px uppercase labels (table headers, card kickers)."""
    return heading(text, 9, 'span', '500', color, ls=ls, transform='uppercase', **kw)


def info_card(kicker, title, text, pad=16, title_size=13, title_url=None):
    return col([micro(kicker), heading(title, title_size, 'span', '400', INK, url=title_url),
                heading(text, 12, 'span', '400', MUTED, lh=1.65)], g=6, padding=dims(pad),
               background_background='classic', background_color='#FFFFFF', border_border='solid',
               border_width=dims(1), border_color=LINE2, border_radius=dims(8))


def card_grid(cards, minw=180):
    # Same auto-fit rule as the design: as many columns as fit at 180px (240px for the contact cards).
    tpl = custom('repeat(auto-fit,minmax(min(100%%,%dpx),1fr))' % minw)
    return grid(cards, 3, g=10, grid_columns_grid=tpl, grid_columns_grid_tablet=tpl, grid_columns_grid_mobile=tpl)


def table(cols, head, rows):
    """A simple table from grid rows of heading widgets; scrolls sideways on phones like the design."""
    tpl = custom(cols)

    def tr(cells, header=False):
        if header:
            items = [micro(c) for c in cells]
        else:
            items = [heading(c, 12, 'span', '500' if i == 0 else '400', INK if i == 0 else BODY2, lh=1.55)
                     for i, c in enumerate(cells)]
        return grid(items, 1, g=12, grid_columns_grid=tpl, grid_columns_grid_tablet=tpl, grid_columns_grid_mobile=tpl,
                    padding=dims(8 if header else 12, 0), border_border='solid', border_width=dims(0, 0, 1, 0),
                    border_color=INK if header else LINE2)

    return con([con([tr(head, True)] + [tr(r) for r in rows], css_classes='bms-table-inner')],
               css_classes='bms-table')


def faq_section(anchor, title, children):
    return col([heading(title, 'clamp(26px,2.8vw,32px)', 'h2', '300', INK, ls=-0.01)] + children, g=22,
               padding=dims(40, 0, 0, 0), border_border='solid', border_width=dims(1, 0, 0, 0), border_color=LINE2,
               _element_id=anchor, css_classes='bms-faq-section')


def faq_text(html, size=13, color=INK):
    return para(html, size, color, 1.7, maxw=600, _element_width_mobile='inherit')


def accordion(items, expanded):
    """Native Elementor (nested) accordion: one question open at a time, opening instantly like the design."""
    children = [{'id': rid(), 'elType': 'container', 'isInner': True, 'settings': {
        'content_width': 'full', 'flex_direction': 'column', 'flex_gap': gap(0), 'padding': dims(0, 0, 16, 0)},
        'elements': [para(a, 12, MUTED, 1.7, maxw=560, _element_width_mobile='inherit')]} for _, a in items]
    w = widget('nested-accordion',
               items=[{'_id': rid(), 'item_title': q, 'element_css_id': ''} for q, _ in items],
               accordion_item_title_position_horizontal='stretch', accordion_item_title_icon_position='end',
               accordion_item_title_icon={'value': 'fas fa-plus', 'library': 'fa-solid'},
               accordion_item_title_icon_active={'value': 'fas fa-minus', 'library': 'fa-solid'},
               title_tag='div', default_state='expanded' if expanded else 'all_collapsed', max_items_expended='one',
               n_accordion_animation_duration=px(0, 'ms'),
               accordion_padding=dims(14, 0), accordion_border_radius=dims(0),
               accordion_background_normal_background='classic', accordion_background_normal_color='rgba(0,0,0,0)',
               accordion_background_hover_background='classic', accordion_background_hover_color='rgba(0,0,0,0)',
               accordion_background_active_background='classic', accordion_background_active_color='rgba(0,0,0,0)',
               accordion_border_normal_border='none', accordion_border_hover_border='none',
               accordion_border_active_border='none', content_border_border='none', content_padding=dims(0, 0, 16, 0),
               **typo('title', 13, '400', lh=1.45),
               normal_title_color=INK, hover_title_color=BLUE, active_title_color=INK,
               normal_icon_color=INK, hover_icon_color=BLUE, active_icon_color=INK, icon_size=px(11),
               _css_classes='bms-faq-acc')
    w['elements'] = children
    return w


FAQS = [
    ('Orders &amp; payment', [
        ('Which payment methods do you accept?', 'PayPal, Visa, Mastercard, Apple Pay and Google Pay, all through a secure checkout.'),
        ('Do I need an account to order?', 'No. You can check out as a guest with just your email and delivery address.'),
        ('Can I change or cancel my order?', 'Email us as soon as possible. If your order hasn’t been packed yet, we’ll happily change or cancel it.'),
        ('Can I add a gift note?', 'Yes. Add a note at checkout and we’ll include it, handwritten, in the parcel.')]),
    ('Products', [
        ('Are your socks unisex?', 'Yes. Choose by foot length using the size guide above.'),
        ('Do the messages fade?', 'No. They’re knitted into the sock, not printed, so they last wash after wash.'),
        ('Do you take bulk orders for churches or groups?', 'Yes. Get in touch and we’ll put together an order for you.')]),
    ('Shipping &amp; returns', [
        ('How do I track my parcel?', 'A tracking link is emailed to you as soon as your parcel leaves us.'),
        ('My parcel says delivered but I don’t have it', 'Check with neighbours and your local post office first, then email us and we’ll help sort it out.'),
        ('Can I return socks I’ve worn?', 'Worn socks can’t be returned for change of mind, but faulty pairs are always replaced.')]),
]


def faq():
    nav = col([heading(t, 12, 'span', '400', INK, url='#' + a, _padding=dims(10, 0), _border_border='solid',
                       _border_width=dims(0, 0, 1, 0), _border_color=LINE2, _css_classes='bms-faq-link')
               for t, a in [('Size &amp; fit', 'size'), ('Materials &amp; care', 'materials'), ('Shipping', 'shipping'),
                            ('Returns &amp; exchanges', 'returns'), ('FAQ', 'faq'), ('Contact', 'contact')]],
              width=px(170), width_tablet=px(170), width_mobile=px(170), css_classes='bms-faq-nav',
              _flex_align_self='flex-start')
    step = lambda n, t, d: grid([heading(n, 15, 'span', '300', BLUE),
                                 col([heading(t, 13, 'span', '400', INK), heading(d, 12, 'span', '400', MUTED, lh=1.6)], g=4)],
                                1, g=12, grid_columns_grid=custom('40px 1fr'), grid_columns_grid_tablet=custom('40px 1fr'),
                                grid_columns_grid_mobile=custom('40px 1fr'), padding=dims(14, 0), border_border='solid',
                                border_width=dims(0, 0, 1, 0), border_color=LINE2)
    main = col([
        col([eyebrow('Support', size=10, ls=0.26), heading('How can we help?', 'clamp(38px,4.6vw,56px)', 'h1', '300', INK,
                                                         ls=-0.02, lh=1),
             heading('Everything about fit, fabric, delivery and returns in one place.', 14, 'p', '400', INK)], g=14),
        faq_section('size', 'Size &amp; fit', [
            faq_text('Our socks come in three sizes, knitted with a little stretch so they hug the foot and keep their shape '
                     'wash after wash. Tees are a relaxed unisex fit.'),
            table('1.2fr 1fr 1fr 1fr', ['Size', 'AU', 'EU', 'Foot length'],
                  [['Small', 'AU 4–7', 'EU 35–38', '22–24 cm'], ['Medium', 'AU 7–11', 'EU 38–42', '24–26.5 cm'],
                   ['Large', 'AU 11–14', 'EU 42–46', '26.5–29 cm']]),
            card_grid([info_card('Height', 'Crew height', 'The cuff sits above the ankle and below the calf, and stays put without pinching.'),
                       info_card('Between sizes?', 'Measure your foot', 'Measure heel to longest toe and use the foot-length column. If you’re between sizes, size up.'),
                       info_card('Tees', 'Relaxed unisex fit', 'Choose your usual size, or size down for a closer fit. Sizes XS to XL.')]),
        ]),
        faq_section('materials', 'Materials &amp; care', [
            card_grid([info_card('Yarn', 'Cotton-rich blend', 'Soft, breathable cotton with a little stretch for comfort and recovery.'),
                       info_card('Construction', 'Knitted, not printed', 'Messages and motifs are knitted into the fabric, so they soften with the sock instead of fading.'),
                       info_card('Finish', 'Reinforced heel &amp; toe', 'Extra strength where socks wear first, so every pair lasts longer.')]),
            micro('Care instructions', ls=0.22, _margin=dims(8, 0, 0, 0)),
            table('90px 1fr', ['Care', 'Instructions'], [
                ['Wash', 'Machine wash cold on a gentle cycle with like colours. Turn inside out first to protect the knitted messages.'],
                ['Dry', 'Line dry in the shade or dry flat. Skip the tumble dryer.'],
                ['Avoid', 'No bleach, no fabric softener, no ironing.'],
                ['Expect', 'Cotton softens over the first few washes. A little surface fluff early on is normal and washes out.']]),
        ]),
        faq_section('shipping', 'Shipping', [
            faq_text('Every order is packed by hand in Australia and sent with Australia Post within one to two business days, with tracking.'),
            card_grid([info_card('Australia', 'Standard · A$9.95', '2–7 business days, tracked.'),
                       info_card('Australia', 'Express · A$14.95', '1–3 business days, tracked.'),
                       info_card('International', 'Calculated at checkout', 'Available countries and costs are shown at checkout.')]),
            table('1.6fr 1fr 1fr', ['Destination', 'Transit', 'Service'], [
                ['Metro areas', '2–5 days', 'Standard'], ['Regional', '4–7 days', 'Standard'], ['Remote', '6–10 days', 'Standard'],
                ['New Zealand', '5–10 days', 'International'], ['Rest of world', '7–15 days', 'International']]),
            faq_text('Estimates are business days after dispatch. International orders may attract local import duties or taxes, '
                     'payable by the recipient.', 11, MUTED),
        ]),
        faq_section('returns', 'Returns &amp; exchanges', [
            faq_text('Nothing in this policy limits your rights under the Australian Consumer Law. Our 30-day promise is an extra '
                     'benefit on top of them.'),
            card_grid([info_card('Faulty pair', 'Free replacement', 'Email us within 30 days with your order number and a photo. We’ll send a new pair at no cost.'),
                       info_card('Change of mind', 'Exchange unworn pairs', 'Email us within 30 days to swap for another colour or size. You cover return postage; we cover the replacement.'),
                       info_card('Worn socks', 'Hygiene restriction', 'For hygiene reasons, worn socks can’t be returned or exchanged for change of mind.')]),
            micro('How to start a return or exchange', ls=0.22, _margin=dims(8, 0, 0, 0)),
            col([step('01', 'Email us', 'Write to hello@blessmysocks.com with your order number, the item and a photo if it’s faulty.'),
                 step('02', 'We reply', 'Within one to two business days, with the return address for exchanges.'),
                 step('03', 'Replacement ships', 'Faulty pairs are re-sent straight away. Exchanges ship once your parcel reaches us.')]),
        ]),
        faq_section('faq', 'FAQ', [col([micro(t, ls=0.22, _padding=dims(0, 0, 6, 0)), accordion(items, i == 0)])
                                   for i, (t, items) in enumerate(FAQS)]),
        faq_section('contact', 'Contact', [
            card_grid([info_card('Email', 'hello@blessmysocks.com', 'Replies within one to two business days. Include your order '
                                 'number for anything about an existing order.', 18, 14, 'mailto:hello@blessmysocks.com'),
                       info_card('Based in', 'Bless My Socks · Australia', 'Bulk and church orders are welcome. Mention “bulk” in '
                                 'your subject line.', 18, 14)], 240),
            button('Send us a message →', SITE + '/contact/', 'dark', 13, (12, 22), _flex_align_self='flex-start'),
        ]),
    ], g=48, width=custom('auto'), width_mobile=custom('auto'), css_classes='bms-faq-main')
    return [mark_inner(boxed([nav, main], width=1080, pad_top=64, pad_bottom=112, flex_direction='row',
                             flex_wrap='wrap', flex_gap=gap(72), flex_gap_tablet=gap(48), flex_gap_mobile=gap(32),
                             flex_align_items='flex-start', css_classes='bms-faq'))]


# ---------------------------------------------------------------- Contact page

def contact():
    def info_row(label, value, url=None):
        return row([heading(label, 14, 'span', '400', MUTED), heading(value, 14, 'span', '400', INK, url=url)],
                   g=16, wrap='nowrap', padding=dims(16, 0), border_border='solid', border_width=dims(0, 0, 1, 0),
                   border_color=LINE2)

    left = col([
        eyebrow('Contact'),
        heading('Get in <em style="color:#3D5A99">touch.</em>', 'clamp(40px,5vw,64px)', 'h1', '300', INK, ls=-0.02, lh=1),
        para('Questions about an order, sizing or bulk orders for your church? Send us a message.', 15, MUTED, 1.7, maxw=420,
             _element_width_mobile='inherit'),
        col([info_row('Email', 'hello@blessmysocks.com', 'mailto:hello@blessmysocks.com'),
             info_row('Replies', 'Within 1–2 business days'), info_row('Based in', 'Australia')],
            border_border='solid', border_width=dims(1, 0, 0, 0), border_color=LINE2, margin=dims(8, 0, 0, 0),
            width=px(420), width_tablet=custom('100%'), width_mobile=custom('100%'), css_classes='bms-contact-info'),
        button('Read the FAQ →', SITE + '/faq/', 'outline', 13, (12, 22), background_color='rgba(0,0,0,0)',
               _flex_align_self='flex-start', _margin=dims(8, 0, 0, 0)),
    ], g=20)
    # Contact Form 7 form (Contact → Contact Forms in WordPress). CF7 has no Elementor widget, so it sits in
    # Elementor's Shortcode widget; deploy.php fills in the form's shortcode. Field styles live in bms.css.
    form = col([widget('shortcode', shortcode='CF7_CONTACT', _css_classes='bms-cf7')],
               background_background='classic', background_color='#FFFFFF', border_border='solid', border_width=dims(1),
               border_color=LINE, border_radius=dims(6), padding=dims(36), padding_tablet=dims(28), padding_mobile=dims(22))
    tpl = custom('repeat(auto-fit,minmax(min(100%,360px),1fr))')
    return [mark_inner(boxed([left, form], pad_top=88, pad_bottom=112, container_type='grid', grid_columns_grid=tpl,
                             grid_columns_grid_tablet=tpl, grid_columns_grid_mobile=tpl, grid_rows_grid=px(1, 'fr'),
                             grid_gaps={'unit': 'px', 'column': '72', 'row': '32', 'isLinked': False},
                             grid_gaps_tablet={'unit': 'px', 'column': '40', 'row': '32', 'isLinked': False},
                             grid_gaps_mobile={'unit': 'px', 'column': '0', 'row': '32', 'isLinked': False},
                             grid_align_items='start'))]


# ---------------------------------------------------------------- UAE header & footer

def header(menu_id):
    brand = row([
        # Client comment: dove and name 3x larger (34px -> 101px, 15px -> 45px on wide screens).
        widget('site-logo', site_logo_fallback='yes', custom_image=img('logo-icon.png'), site_logo_size_size='full',
               align='left', width=px(101), width_tablet=px(72), width_mobile=px(45), link_to='default',
               space=px(100, '%'), _element_width='auto'),
        widget('hfe-site-title', heading_tag='span', heading_link='default', heading_text_align='left',
               after='<sup class="bms-reg">®</sup>',
               heading_color=INK, **typo('heading', 'clamp(22px,3.1vw,45px)', '400', ls=0.22, transform='uppercase',
                                         size_mobile=16),
               heading_typography_letter_spacing_tablet=px(0.16, 'em'),
               heading_typography_letter_spacing_mobile=px(0.14, 'em'), _element_width='auto'),
    ], 'flex-start', g=10, wrap='nowrap', css_classes='bms-brand', _flex_align_self='center')
    nav = widget('navigation-menu', menu=str(menu_id), layout='horizontal', navmenu_align='center', dropdown='mobile',
                 resp_align='right', hamburger_align='right', full_width_dropdown='yes', submenu_icon='arrow',
                 pointer='none', padding_horizontal_menu_item=px(0), padding_vertical_menu_item=px(6),
                 menu_space_between=px(28), menu_space_between_tablet=px(18),
                 color_menu_item=INK, color_menu_item_hover=BLUE, color_menu_item_active=INK,
                 **typo('menu', 15, '400', ls=0.02),
                 background_color_dropdown_item=BG, color_dropdown_item=INK, color_dropdown_item_hover=BLUE,
                 background_color_dropdown_item_hover='#FFFFFF', padding_horizontal_dropdown_item=px(24),
                 padding_vertical_dropdown_item=px(14), distance_from_menu=px(13),
                 **typo('dropdown', 15, '400'),
                 toggle_color=INK, toggle_background_color='rgba(0,0,0,0)', toggle_size=px(20),
                 toggle_border_width=px(0), toggle_border_radius=px(999))
    cart = widget('hfe-cart', hfe_cart_type='custom', items_indicator='bubble', show_subtotal='', align='right',
                  **typo('toggle_button', 13, '400'), toggle_button_border_width=dims(0),
                  toggle_button_border_radius=dims(999), toggle_button_padding=dims(8, 14),
                  toggle_button_text_color='#FFFFFF', toggle_button_background_color=INK,
                  toggle_button_hover_text_color='#FFFFFF', toggle_button_hover_background_color=BLUE,
                  items_indicator_text_color=INK, items_indicator_background_color='#FFFFFF',
                  items_indicator_text_hover_color=INK, items_indicator_background_hover_color='#FFFFFF',
                  _css_classes='bms-cart', _element_width='auto', _flex_align_self='center')
    bar_inner = con([brand, nav, cart], content_width='boxed', boxed_width=px(1320), container_type='grid',
                    grid_columns_grid=custom('auto 1fr auto'), grid_columns_grid_tablet=custom('auto 1fr auto'),
                    grid_columns_grid_mobile=custom('auto 1fr auto'), grid_rows_grid=px(1, 'fr'),
                    grid_gaps={'unit': 'px', 'column': '16', 'row': '0', 'isLinked': False}, grid_align_items='center',
                    # Phones: hamburger sits right beside the Cart pill.
                    grid_gaps_mobile={'unit': 'px', 'column': '4', 'row': '0', 'isLinked': False},
                    grid_justify_items='stretch', padding=dims(12, 40), padding_tablet=dims(12, 24),
                    padding_mobile=dims(10, 16))
    return [mark_inner(con([
        con([heading('The new sock range is on its way · Sign up to hear first', 12, 'span', '400', '#FFFFFF',
                     align='center', ls=0.08, lh=16, lh_unit='px', size_mobile=11,
                     typography_letter_spacing_mobile=px(0.02, 'em'))],
            background_background='classic', background_color=INK, padding=dims(9, 16), min_height=px(34),
            flex_justify_content='center', css_classes='bms-announcement'),
        con([bar_inner], background_background='classic', background_color='rgba(244,243,241,0.94)',
            border_border='solid', border_width=dims(0, 0, 1, 0), border_color=LINE, css_classes='bms-bar'),
    ], css_classes='bms-header'))]


def footer(menus):
    def menu_col(title, menu_id):
        return col([heading(title, 10, 'span', '500', '#A9B8D8', ls=0.24),
                    widget('navigation-menu', menu=str(menu_id), layout='vertical', navmenu_align='left', dropdown='none',
                           pointer='none', padding_horizontal_menu_item=px(0), padding_vertical_menu_item=px(5),
                           menu_space_between=px(0), color_menu_item='#FFFFFF', color_menu_item_hover='#A9B8D8',
                           color_menu_item_active='#FFFFFF', **typo('menu', 13, '400'),
                           _css_classes='bms-footer-menu')], g=10)

    top = grid([
        col([image('logo-footer-white.png', 'medium', SITE + '/', width=px(150), align='left'),
             heading('Christian socks, tees and gifts. Shipped from Australia.', 13, 'span', '400', '#C9CCD2', lh=1.6,
                     _element_width='initial', _element_custom_width=px(300), _element_width_tablet='inherit')],
            g=14, grid_column='custom', grid_column_custom='span 2', grid_column_custom_tablet='span 3',
            grid_column_custom_mobile='span 3'),
        menu_col('SHOP', menus['shop']),
        menu_col('HELP', menus['help']),
        menu_col('COMPANY', menus['company']),
    ], 5, 3, 'auto auto auto', 36, rg=36,
        # Phones: Shop / Help / Company share one row, each column as wide as its links.
        grid_justify_content_mobile='space-between',
        grid_gaps_mobile={'unit': 'px', 'column': '16', 'row': '32', 'isLinked': False})
    bottom = row([
        widget('copyright', shortcode='© 2026 Bless My Socks® · Australia. Bless My Socks® and Bless My® are registered '
                                      'trademarks. All Rights Reserved.', align='left', title_color='#C9CCD2',
               **typo('caption', 12, '400')),
        heading('Secure checkout · PayPal · Stripe', 12, 'span', '400', '#C9CCD2'),
    ], g=16, padding=dims(20, 0, 0, 0), border_border='solid', border_width=dims(1, 0, 0, 0), border_color='#343944')
    return [mark_inner(con([
        boxed([top, bottom], pad_top=72, pad_bottom=28, flex_gap=gap(48)),
    ], background_background='classic', background_color=INK, css_classes='bms-footer'))]


if __name__ == '__main__':
    root = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'elementor')
    os.makedirs(root, exist_ok=True)
    # Menu IDs are filled in on the server by tools/deploy.php.
    menus = {'primary': 'MENU_PRIMARY', 'shop': 'MENU_SHOP', 'help': 'MENU_HELP', 'company': 'MENU_COMPANY'}
    for name, data in (('home', home()), ('about', about()), ('faq', faq()), ('contact', contact()), ('header', header(menus['primary'])), ('footer', footer(menus))):
        with open(os.path.join(root, name + '.json'), 'w') as fh:
            json.dump(data, fh, ensure_ascii=False, separators=(',', ':'))
        print(name, len(json.dumps(data)))
