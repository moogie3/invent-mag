/**
 * HTML Sanitization Utilities
 * 
 * Provides functions to escape HTML entities and prevent XSS attacks
 * when dynamically inserting content into the DOM.
 */

/**
 * Escapes HTML special characters to prevent XSS attacks
 * 
 * @param {string} unsafe - The unsafe string that may contain HTML
 * @returns {string} - The safely escaped string
 * 
 * @example
 * const userInput = '<script>alert("XSS")</script>';
 * const safe = escapeHtml(userInput);
 * // Result: '&lt;script&gt;alert("XSS")&lt;/script&gt;'
 */
export function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') {
        return unsafe;
    }
    
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Strips all HTML tags from a string
 * 
 * @param {string} html - The HTML string to strip
 * @returns {string} - The text content without HTML tags
 * 
 * @example
 * const html = '<p>Hello <strong>World</strong></p>';
 * const text = stripHtml(html);
 * // Result: 'Hello World'
 */
export function stripHtml(html) {
    if (typeof html !== 'string') {
        return html;
    }
    
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

/**
 * Sanitizes a string for safe use in HTML attributes
 * 
 * @param {string} str - The string to sanitize
 * @returns {string} - The sanitized string
 */
export function sanitizeAttribute(str) {
    if (typeof str !== 'string') {
        return str;
    }
    
    return str
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * Creates a text node safely (alternative to innerHTML)
 * 
 * @param {HTMLElement} element - The element to set text content
 * @param {string} text - The text content to set
 * 
 * @example
 * const div = document.createElement('div');
 * setTextContent(div, userInput); // Safe, no XSS risk
 */
export function setTextContent(element, text) {
    element.textContent = text;
}

/**
 * Safely creates an element with text content
 * 
 * @param {string} tagName - The HTML tag name
 * @param {string} textContent - The text content
 * @param {Object} attributes - Optional attributes to set
 * @returns {HTMLElement} - The created element
 * 
 * @example
 * const div = createSafeElement('div', userInput, { class: 'message' });
 */
export function createSafeElement(tagName, textContent = '', attributes = {}) {
    const element = document.createElement(tagName);
    
    if (textContent) {
        element.textContent = textContent;
    }
    
    for (const [key, value] of Object.entries(attributes)) {
        if (key === 'class') {
            element.className = value;
        } else if (key === 'style' && typeof value === 'object') {
            Object.assign(element.style, value);
        } else {
            element.setAttribute(key, value);
        }
    }
    
    return element;
}

export default {
    escapeHtml,
    stripHtml,
    sanitizeAttribute,
    setTextContent,
    createSafeElement
};
