"use strict";(self.webpackChunkgform_conversational_forms=self.webpackChunkgform_conversational_forms||[]).push([[108],{5888:function(e,t,n){n.r(t),n.d(t,{default:function(){return N}});var a=n(7784),o=n(1125),r=n(5518),l=a.ReactDOM.createRoot,i=n(3944),d=a.ReactDOM.createRoot,u=n(9801),c=n(9509),s=n.n(c),v=n(7413),p=n.n(v),f=n(6313),m=a.ReactDOM.createRoot,g=function(e){var t=e.detail.id,n=window.wp.media({library:{type:"image"},multiple:!1});n.on("select",(function(){var e=n.state().get("selection").first().toJSON(),a=e.url,o=void 0===a?"":a,r=e.id,l=void 0===r?"":r,i=e.sizes,d=(void 0===i?{}:i).large,u=(void 0===d?{}:d).url;h(t,l,void 0===u?"":u,o)})),n.open()},_=function(){var e=(0,u.Z)(s().mark((function e(t){var n,a,o,r,l,i,d,u,c,v,f,m,g,_,w,k;return s().wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return e.prev=0,(v=new FormData).append("name","fileUpload"),v.append("action","upload-attachment"),v.append("_wpnonce",(null===(n=_wpPluploadSettings)||void 0===n||null===(a=n.defaults)||void 0===a||null===(o=a.multipart_params)||void 0===o?void 0:o._wpnonce)||""),v.append("async-upload",t.detail.file),e.next=8,fetch((null===p()||void 0===p()||null===(r=p().endpoints)||void 0===r?void 0:r.ajaxurl)||"",{method:"POST",credentials:"same-origin",body:v});case 8:return f=e.sent,e.next=11,f.json();case 11:m=e.sent,g=t.detail.id,_=null==m||null===(l=m.data)||void 0===l?void 0:l.id,w=null==m||null===(i=m.data)||void 0===i?void 0:i.url,k=null==m||null===(d=m.data)||void 0===d||null===(u=d.sizes)||void 0===u||null===(c=u.large)||void 0===c?void 0:c.url,h(g,_,k,w),e.next=22;break;case 19:e.prev=19,e.t0=e.catch(0),console.error(e.t0);case 22:case"end":return e.stop()}}),e,null,[[0,19]])})));return function(t){return e.apply(this,arguments)}}(),h=function(e,t,n,a){(0,r.trigger)({event:"gform/file_upload/external_manager/file_selected",native:!1,data:{fileUploadId:e,id:t,largeUrl:n,url:a}})},w={},k=function(e){var t,n;e.preventDefault();var a=e.target;if("button-icon"!==e.target.dataset.js&&"button-active-text"!==e.target.dataset.js||(a=e.target.parentNode),(0,r.getNodes)("permalink-input-value",!0,a.parentNode)[0].value){var o="".concat((null===(t=a.dataset)||void 0===t?void 0:t.jsRoot)||"").concat(null===(n=a.dataset)||void 0===n?void 0:n.savedValue);window.open(o,"_blank")}},N=function(){(0,r.getNodes)("gform-input--range",!0).forEach((function(e){var t,n=(null==e||null===(t=e.dataset)||void 0===t?void 0:t.jsProps)||"{}",r=JSON.parse(n);l(e).render(a.React.createElement(o.Z,r))})),(0,r.getNodes)("gform-input--swatch",!0).forEach((function(e){var t,n=(null==e||null===(t=e.dataset)||void 0===t?void 0:t.jsProps)||"{}",o=JSON.parse(n);d(e).render(a.React.createElement(i.Z,o))})),(0,r.getNodes)("gform-input--file-upload",!0).forEach((function(e){var t,n=(null==e||null===(t=e.dataset)||void 0===t?void 0:t.jsProps)||"{}",o=JSON.parse(n);m(e).render(a.React.createElement(f.Z,o))})),document.addEventListener("gform/file_upload/external_manager/open",g),document.addEventListener("gform/file_upload/external_manager/save",_),w.permalinkActions=(0,r.getNodes)("permalink-action-button",!0),w.permalinkActions.forEach((function(e){return e.addEventListener("click",k)}))}}}]);
//# sourceMappingURL=scripts-admin.settings.121dad122d9d6856a07d.js.map