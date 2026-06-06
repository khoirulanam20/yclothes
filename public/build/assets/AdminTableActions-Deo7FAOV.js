import{c as i,j as e,b as m,B as c,L as x}from"./app-jFXESXSb.js";import{E as j,T as f,d as A,e as k}from"./AdminTopBar-DZkkY_MQ.js";import{P as v}from"./pencil-HZ22e99f.js";import{C as y,a as b}from"./copy-CFgLeDwp.js";import{X as g}from"./x-xcJIC4Yy.js";import{T as M}from"./trash-2-BJYMc68r.js";/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const T=[["path",{d:"M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0",key:"1nclc0"}],["circle",{cx:"12",cy:"12",r:"3",key:"1v7zrd"}]],_=i("eye",T);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=[["path",{d:"M12 10v6",key:"1bos4e"}],["path",{d:"M9 13h6",key:"1uhe8q"}],["path",{d:"M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z",key:"1kt360"}]],N=i("folder-plus",z);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const C=[["path",{d:"M3 12h.01",key:"nlz23k"}],["path",{d:"M3 18h.01",key:"1tta3j"}],["path",{d:"M3 6h.01",key:"1rqtza"}],["path",{d:"M8 12h13",key:"1za7za"}],["path",{d:"M8 18h13",key:"1lx6n3"}],["path",{d:"M8 6h13",key:"ik3vkj"}]],L=i("list",C);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const E=[["rect",{width:"18",height:"18",x:"3",y:"3",rx:"2",key:"afitv7"}],["path",{d:"M3 9h18",key:"1pudct"}],["path",{d:"M9 21V9",key:"1oto5p"}]],P=i("panels-top-left",E);function q({children:t}){return e.jsx("div",{className:"flex items-center justify-end gap-0.5",children:t})}function n({label:t,icon:r,href:s,onClick:l,variant:d="default",target:u,rel:h}){const o=m("size-8",d==="destructive"&&"text-destructive hover:bg-destructive/10 hover:text-destructive"),a=e.jsxs(e.Fragment,{children:[e.jsx(r,{className:"size-4"}),e.jsx("span",{className:"sr-only",children:t})]}),p=s?e.jsx(c,{variant:"ghost",size:"icon",className:o,asChild:!0,children:e.jsx(x,{href:s,target:u,rel:h,children:a})}):e.jsx(c,{type:"button",variant:"ghost",size:"icon",className:o,onClick:l,children:a});return e.jsxs(f,{children:[e.jsx(A,{asChild:!0,children:p}),e.jsx(k,{side:"top",children:t})]})}function F({href:t}){return e.jsx(n,{label:"Edit",icon:v,href:t})}function H({href:t}){return e.jsx(n,{label:"Detail",icon:_,href:t})}function R({onClick:t}){return e.jsx(n,{label:"Hapus",icon:M,variant:"destructive",onClick:t})}function X({onClick:t}){return e.jsx(n,{label:"Duplikat",icon:b,onClick:t})}function Z({onClick:t}){return e.jsx(n,{label:"Setujui",icon:y,onClick:t})}function G({onClick:t}){return e.jsx(n,{label:"Tolak",icon:g,variant:"destructive",onClick:t})}function J({href:t}){return e.jsx(n,{label:"Builder",icon:P,href:t})}function K({href:t}){return e.jsx(n,{label:"Preview",icon:j,href:t,target:"_blank",rel:"noopener noreferrer"})}function O({href:t}){return e.jsx(n,{label:"Sub kategori",icon:N,href:t})}function Q({href:t}){return e.jsx(n,{label:"Items",icon:L,href:t})}export{q as A,F as a,O as b,J as c,K as d,Q as e,H as f,R as g,X as h,Z as i,G as j};
