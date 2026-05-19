// Global Event Delegator for CSP Compliance
// This intercepts data-onclick, data-onchange, and data-onsubmit attributes
// and executes them via eval(), avoiding inline event handler CSP violations.

document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-onclick]');
    if (el) {
        // If it's an anchor tag with href="#", prevent default jump
        if (el.tagName === 'A' && el.getAttribute('href') === '#') {
            e.preventDefault();
        }
        var code = el.getAttribute('data-onclick');
        // Provide 'this' context as the element
        (function(event) { eval(code); }).call(el, e);
    }
});

document.addEventListener('change', function(e) {
    var el = e.target.closest('[data-onchange]');
    if (el) {
        var code = el.getAttribute('data-onchange');
        (function(event) { eval(code); }).call(el, e);
    }
});

document.addEventListener('submit', function(e) {
    var el = e.target.closest('[data-onsubmit]');
    if (el) {
        var code = el.getAttribute('data-onsubmit');
        var result = (function(event) { return eval(code); }).call(el, e);
        if (result === false) {
            e.preventDefault();
        }
    }
});
