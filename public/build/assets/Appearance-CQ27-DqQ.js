import {
    Gt as e,
    J as t,
    L as n,
    Y as r,
    Z as i,
    Zt as a,
    ar as o,
    it as s,
    ot as c,
    r as l,
    tn as u,
    vr as d,
    xr as f,
} from './dist-NQh-iFE5.js';
import { t as p } from './createLucideIcon-DciPPFih.js';
import { L as m, n as h, r as g } from './app-BfgF5Uv5.js';
var _ = p(`monitor`, [
        [
            `rect`,
            {
                width: `20`,
                height: `14`,
                x: `2`,
                y: `3`,
                rx: `2`,
                key: `48i651`,
            },
        ],
        [`line`, { x1: `8`, x2: `16`, y1: `21`, y2: `21`, key: `1svkeh` }],
        [`line`, { x1: `12`, x2: `12`, y1: `17`, y2: `21`, key: `vw1qmm` }],
    ]),
    v = p(`moon`, [
        [
            `path`,
            {
                d: `M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401`,
                key: `kfwtm`,
            },
        ],
    ]),
    y = p(`sun`, [
        [`circle`, { cx: `12`, cy: `12`, r: `4`, key: `4exip2` }],
        [`path`, { d: `M12 2v2`, key: `tus03m` }],
        [`path`, { d: `M12 20v2`, key: `1lh1kg` }],
        [`path`, { d: `m4.93 4.93 1.41 1.41`, key: `149t6j` }],
        [`path`, { d: `m17.66 17.66 1.41 1.41`, key: `ptbguv` }],
        [`path`, { d: `M2 12h2`, key: `1t8f8n` }],
        [`path`, { d: `M20 12h2`, key: `1q8mjw` }],
        [`path`, { d: `m6.34 17.66-1.41 1.41`, key: `1m8zz5` }],
        [`path`, { d: `m19.07 4.93-1.41 1.41`, key: `1shlcs` }],
    ]),
    b = {
        class: `inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800`,
    },
    x = [`onClick`],
    S = { class: `ml-1.5 text-sm` },
    C = c({
        __name: `AppearanceTabs`,
        setup(s) {
            let { appearance: c, updateAppearance: l } = m(),
                p = [
                    { value: `light`, Icon: y, label: `Light` },
                    { value: `dark`, Icon: v, label: `Dark` },
                    { value: `system`, Icon: _, label: `System` },
                ];
            return (s, m) => (
                e(),
                i(`div`, b, [
                    (e(),
                    i(
                        n,
                        null,
                        a(p, ({ value: n, Icon: i, label: a }) =>
                            t(
                                `button`,
                                {
                                    key: n,
                                    onClick: (e) => o(l)(n),
                                    class: d([
                                        `flex items-center rounded-md px-3.5 py-1.5 transition-colors`,
                                        o(c) === n
                                            ? `bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100`
                                            : `text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60`,
                                    ]),
                                },
                                [
                                    (e(), r(u(i), { class: `-ml-1 h-4 w-4` })),
                                    t(`span`, S, f(a), 1),
                                ],
                                10,
                                x,
                            ),
                        ),
                        64,
                    )),
                ])
            );
        },
    }),
    w = { class: `space-y-6` },
    T = c({
        layout: { breadcrumbs: [{ title: `Appearance settings`, href: h() }] },
        __name: `Appearance`,
        setup(r) {
            return (r, a) => (
                e(),
                i(
                    n,
                    null,
                    [
                        s(o(l), { title: `Appearance settings` }),
                        (a[0] ||= t(
                            `h1`,
                            { class: `sr-only` },
                            `Appearance settings`,
                            -1,
                        )),
                        t(`div`, w, [
                            s(g, {
                                variant: `small`,
                                title: `Appearance settings`,
                                description: `Update the appearance settings for your account`,
                            }),
                            s(C),
                        ]),
                    ],
                    64,
                )
            );
        },
    });
export { T as default };
