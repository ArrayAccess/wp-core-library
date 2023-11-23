/*!
 * Admin Library JS init
 */
(function (w) {
    "use strict";
    const init = function () {
        //const wp = w.wp||null;
        //const $ = w.jQuery||null;
    };

    // init on document ready
    if (w.document.readyState === "complete" || (w.document.readyState !== "loading")) {
        // if document is already ready, then init
        init();
    } else {
        // if document hasn't finished loading, add event listener
        w.document.addEventListener("DOMContentLoaded", init);
    }
})(window);
