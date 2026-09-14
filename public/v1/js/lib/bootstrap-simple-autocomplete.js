/**
 * BootstrapSimpleAutocomplete Class v1.1
 * A simple and lightweight autocomplete component for Bootstrap 5.
 * https://github.com/osalabs/bootstrap-simple-autocomplete
 * (c) 2024-present Oleg Savchuk
 * @license MIT
 *
 * Usage:
 * Include this script in your HTML and add the attribute 'data-autocomplete="URL"' to your input elements.
 *
 * Example:
 * <input type="text" data-autocomplete="https://localhost?q=" class="form-control">
 * <script src="bootstrap-simple-autocomplete.js"></script>
 *
 * ES6 Module Usage:
 * import { BootstrapSimpleAutocomplete, initializeAutocomplete } from 'bootstrap-simple-autocomplete.mjs';
 *
 * Initialize:
 * initializeAutocomplete(); // To auto-initialize all elements with data-autocomplete attribute
 * or
 * new BootstrapSimpleAutocomplete(document.querySelector('input[data-autocomplete]')); // To initialize specific element
 */
class BootstrapSimpleAutocomplete {
    constructor(input, options = {}) {
        this.input = input;
        this.url = input.getAttribute('data-autocomplete');

        // Parse options
        this.debounceDelay = options.debounceDelay || parseInt(input.getAttribute('data-debounce')) || 300;
        this.minQueryLength = options.minQueryLength || parseInt(input.getAttribute('data-minlength')) || 1;
        this.fetchFunction = options.fetchFunction || this.defaultFetchFunction.bind(this);
        this.renderItem = options.renderItem || this.defaultRenderItem.bind(this);

        // Create wrapper and dropdown elements
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        this.dropdown = document.createElement('div');
        this.dropdown.className = 'dropdown-menu';
        this.dropdown.style.maxHeight = '224px'; // Limit the height to show 7 items (each item ~32px height)
        this.dropdown.style.overflowY = 'auto';  // Make it scrollable
        wrapper.appendChild(this.dropdown);

        // Create debounced version of fetchData
        this.debouncedFetchData = this.debounce(this.fetchData.bind(this), this.debounceDelay);

        // Store bound event handlers for later removal
        this.onInputHandler = this.onInput.bind(this);
        this.onKeyDownHandler = this.onKeyDown.bind(this);
        this.onClickOutsideHandler = this.onClickOutside.bind(this);

        this.addEventListeners();
        this.insertStyles();

        // Accessibility enhancements
        this.id = Math.random().toString(36).substr(2, 9);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', `autocomplete-list-${this.id}`);
        this.dropdown.id = `autocomplete-list-${this.id}`;
    }

    dispatch(type, detail = {}) {
        const evdata = { bubbles: true, composed: true, detail };
        this.input.dispatchEvent(new CustomEvent(type, evdata));
    }

    debounce(fn, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    addEventListeners() {
        this.input.addEventListener('input', this.onInputHandler);
        this.input.addEventListener('keydown', this.onKeyDownHandler);
        document.addEventListener('click', this.onClickOutsideHandler, { passive: true });
    }

    onInput(event) {
        const query = event.target.value;
        if (query.length < this.minQueryLength) {
            this.closeDropdown();
            return;
        }

        // Prefill input immediately
        const firstOption = this.dropdown.querySelector('.dropdown-item');
        if (firstOption && firstOption.textContent.toLowerCase().startsWith(query.toLowerCase())) {
            this.prefillInput(firstOption.textContent, query);
        } else {
            this.clearPrefill();
        }

        this.debouncedFetchData(query);
    }

    fetchData(query) {
        this.dispatch('autocomplete-fetch', { query });
        this.fetchFunction(query)
            .then(data => {
                this.dispatch('autocomplete-response', { query, results: data });
                this.showDropdown(data, query);
            })
            .catch(err => this.showError(err, query));
    }

    defaultFetchFunction(query) {
        return fetch(`${this.url}${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json'
            }
        }).then(response => response.json());
    }

    onKeyDown(event) {
        const hasSuggestions = this.dropdown.querySelector('.dropdown-item');
        if ((event.key === 'Tab' || event.key === 'Enter') && hasSuggestions) {
            event.preventDefault();
            const activeOption = this.dropdown.querySelector('.dropdown-item.active');
            if (activeOption) {
                this.selectOption(activeOption.textContent);
            } else {
                const firstOption = this.dropdown.querySelector('.dropdown-item');
                if (firstOption) {
                    this.selectOption(firstOption.textContent);
                }
            }
        } else if (event.key === 'Escape') {
            this.closeDropdown('escape');
        } else if (event.key === 'ArrowDown') {
            event.preventDefault(); // Prevent cursor movement
            this.navigateDropdown('down');
        } else if (event.key === 'ArrowUp') {
            event.preventDefault(); // Prevent cursor movement
            this.navigateDropdown('up');
        }
    }

    onClickOutside(event) {
        if (!this.dropdown.contains(event.target) && event.target !== this.input) {
            this.closeDropdown('blur');
        }
    }

    defaultRenderItem(option, query, index) {
        const item = document.createElement('a');
        item.className = 'dropdown-item';
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', 'false');
        item.textContent = option;
        item.id = `autocomplete-item-${this.id}-${index}`;
        item.addEventListener('click', () => this.selectOption(option));
        return item;
    }

    showDropdown(options, query) {
        this.dropdown.innerHTML = '';
        if (options.length > 0) {
            const fragment = document.createDocumentFragment();
            const firstOption = options[0];
            if (typeof firstOption === 'string' && firstOption.toLowerCase().startsWith(query.toLowerCase())) {
                this.prefillInput(firstOption, query);
            }
            options.forEach((option, index) => {
                const item = this.renderItem(option, query, index);
                fragment.appendChild(item);
            });
            this.dropdown.appendChild(fragment);
            this.dropdown.classList.add('show');
            this.dispatch('autocomplete-open', { query, results: options });
        } else {
            this.dispatch('autocomplete-no-results', { query });
            this.closeDropdown();
        }
    }

    prefillInput(option, query) {
        const prefillSpan = this.input.parentNode.querySelector('.autocomplete-suggestion');
        if (prefillSpan) {
            prefillSpan.textContent = query + option.slice(query.length);
        } else {
            const newPrefillSpan = document.createElement('span');
            newPrefillSpan.className = 'autocomplete-suggestion';
            newPrefillSpan.textContent = query + option.slice(query.length);
            this.input.parentNode.appendChild(newPrefillSpan);

            // Adjust the position and size to match input
            const inputRect = this.input.getBoundingClientRect();
            const inputStyles = window.getComputedStyle(this.input);
            newPrefillSpan.style.height = `${inputRect.height}px`;
            newPrefillSpan.style.width = `${inputRect.width}px`;
            newPrefillSpan.style.fontSize = inputStyles.fontSize;
            newPrefillSpan.style.fontFamily = inputStyles.fontFamily;
            newPrefillSpan.style.fontWeight = inputStyles.fontWeight;
            newPrefillSpan.style.fontStyle = inputStyles.fontStyle;
            newPrefillSpan.style.color = inputStyles.color;
            // Set padding from input but also add input border size to padding sizes
            newPrefillSpan.style.paddingLeft = (parseInt(inputStyles.paddingLeft) + parseInt(inputStyles.borderLeftWidth)) + 'px';
            newPrefillSpan.style.paddingTop = (parseInt(inputStyles.paddingTop) + parseInt(inputStyles.borderTopWidth)) + 'px';
            newPrefillSpan.style.paddingRight = (parseInt(inputStyles.paddingRight) + parseInt(inputStyles.borderRightWidth)) + 'px';
            newPrefillSpan.style.paddingBottom = (parseInt(inputStyles.paddingBottom) + parseInt(inputStyles.borderBottomWidth)) + 'px';
        }
    }

    clearPrefill() {
        const prefillSpan = this.input.parentNode.querySelector('.autocomplete-suggestion');
        if (prefillSpan) {
            prefillSpan.remove();
        }
    }

    selectOption(option) {
        this.input.value = option;
        this.closeDropdown('select');

        const evdata = { value: option };
        this.dispatch('autocomplete', evdata);
    }

    closeDropdown(reason) {
        const wasOpen = this.dropdown.classList.contains('show');
        this.dropdown.classList.remove('show');
        this.dropdown.innerHTML = '';
        this.clearPrefill();
        this.input.removeAttribute('aria-activedescendant');
        if (wasOpen && reason) {
            this.dispatch('autocomplete-close', { reason });
        }
    }

    showError(err, query) {
        console.error(err);
        this.dropdown.innerHTML = '';
        const error = document.createElement('div');
        error.className = 'dropdown-item text-danger';
        error.textContent = err.message === 'Failed to fetch' ? 'Network error' : 'Error fetching results';
        error.style.pointerEvents = 'none';
        this.dropdown.appendChild(error);
        this.dropdown.classList.add('show');
        this.dispatch('autocomplete-error', { query, error: err });
    }

    navigateDropdown(direction) {
        const items = Array.from(this.dropdown.querySelectorAll('.dropdown-item'));
        if (items.length === 0) return;
        let activeIndex = items.findIndex(item => item.classList.contains('active'));
        if (direction === 'down') {
            activeIndex = (activeIndex + 1) % items.length;
        } else {
            activeIndex = (activeIndex - 1 + items.length) % items.length;
        }
        items.forEach((item, index) => {
            if (index === activeIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
                item.setAttribute('aria-selected', 'true');
                this.input.setAttribute('aria-activedescendant', item.id);
                this.prefillInput(item.textContent, this.input.value);
                this.dispatch('autocomplete-highlight', { value: item.textContent, index });
            } else {
                item.classList.remove('active');
                item.setAttribute('aria-selected', 'false');
            }
        });
    }

    insertStyles() {
        if (document.getElementById('bootstrap-simple-autocomplete-styles')) return;

        const style = document.createElement('style');
        style.id = 'bootstrap-simple-autocomplete-styles';
        style.innerHTML = `
            .autocomplete-suggestion {
                position: absolute;
                left: 0;
                top: 0;
                z-index: 10;
                pointer-events: none;
                opacity: 0.5;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Destroy the autocomplete instance and clean up event listeners
     */
    destroy() {
        this.input.removeEventListener('input', this.onInputHandler);
        this.input.removeEventListener('keydown', this.onKeyDownHandler);
        document.removeEventListener('click', this.onClickOutsideHandler, { passive: true });
        this.clearPrefill();
        this.closeDropdown('blur');
    }
}

/**
 * Initialize all autocomplete inputs on the page.
 */
function initializeAutocomplete() {
    const inputs = document.querySelectorAll('input[data-autocomplete]');
    inputs.forEach(input => new BootstrapSimpleAutocomplete(input));
}

// Check if running as a module or script
if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeAutocomplete();
    });
}

// Export the class and initialization function for module usage
if (typeof exports !== 'undefined') {
    exports.BootstrapSimpleAutocomplete = BootstrapSimpleAutocomplete;
    exports.initializeAutocomplete = initializeAutocomplete;
}
