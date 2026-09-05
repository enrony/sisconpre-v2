import {
    En as e,
    Gt as t,
    J as n,
    O as r,
    Vn as i,
    Z as a,
    ar as o,
    k as s,
    ot as c,
    vr as l,
    xr as u,
} from './dist-NQh-iFE5.js';
import { a as d } from './button-CTW30sKl.js';
import { h as f } from './app-BfgF5Uv5.js';
var p = c({
        __name: `Input`,
        props: {
            defaultValue: {},
            modelValue: {},
            class: { type: [Boolean, null, String, Object, Array] },
        },
        emits: [`update:modelValue`],
        setup(n, { emit: s }) {
            let c = n,
                u = f(c, `modelValue`, s, {
                    passive: !0,
                    defaultValue: c.defaultValue,
                });
            return (n, s) =>
                e(
                    (t(),
                    a(
                        `input`,
                        {
                            'onUpdate:modelValue': (s[0] ||= (e) =>
                                i(u) ? (u.value = e) : null),
                            'data-slot': `input`,
                            class: l(
                                o(d)(
                                    `file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm`,
                                    `focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]`,
                                    `aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive`,
                                    c.class,
                                ),
                            ),
                        },
                        null,
                        2,
                    )),
                    [[r, o(u)]],
                );
        },
    }),
    m = { class: `text-sm text-red-600 dark:text-red-500` },
    h = c({
        __name: `InputError`,
        props: { message: {} },
        setup(r) {
            return (i, o) =>
                e((t(), a(`div`, null, [n(`p`, m, u(r.message), 1)], 512)), [
                    [s, r.message],
                ]);
        },
    });
export { p as n, h as t };
