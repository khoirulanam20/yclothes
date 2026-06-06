import{c as i,r as l,j as a,f as d,B as p,K as s}from"./app-CG9plFxt.js";/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=[["path",{d:"M20 6 9 17l-5-5",key:"1gmf2c"}]],m=i("check",u);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=[["rect",{width:"14",height:"14",x:"8",y:"8",rx:"2",ry:"2",key:"17jyea"}],["path",{d:"M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2",key:"zix9uf"}]],h=i("copy",y);async function x(t){var n;try{if((n=navigator.clipboard)!=null&&n.writeText)return await navigator.clipboard.writeText(t),!0}catch{}try{const e=document.createElement("textarea");e.value=t,e.style.position="fixed",e.style.opacity="0",document.body.appendChild(e),e.select();const o=document.execCommand("copy");return document.body.removeChild(e),o}catch{return!1}}function k({amount:t,label:n,className:e}){const[o,c]=l.useState(!1),r=async()=>{if(!await x(String(t))){s.error("Gagal menyalin nominal.");return}c(!0),s.success("Nominal disalin."),window.setTimeout(()=>c(!1),2e3)};return a.jsxs("span",{className:`inline-flex items-center gap-1.5 ${e??""}`,children:[a.jsx("span",{children:n??d(t)}),a.jsx(p,{type:"button",variant:"ghost",size:"icon",className:"h-7 w-7 shrink-0",onClick:r,"aria-label":"Salin nominal",title:"Salin",children:o?a.jsx(m,{className:"h-4 w-4 text-green-600"}):a.jsx(h,{className:"h-4 w-4"})})]})}export{k as C};
