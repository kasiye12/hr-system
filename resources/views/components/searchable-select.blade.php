@props(['name', 'id', 'options' => [], 'placeholder' => 'Search...', 'required' => false, 'selected' => null])

<div class="searchable-select-wrapper" style="position: relative;">
    <input type="text" 
           class="searchable-select-input"
           id="{{ $id }}_search"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           style="width: 100%;"
           onfocus="showSearchableDropdown('{{ $id }}')"
           onkeyup="filterSearchableOptions('{{ $id }}')">
    
    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #627386; pointer-events: none;">
        🔍
    </span>
    
    <div id="{{ $id }}_dropdown" 
         class="searchable-select-dropdown"
         style="display: none; position: absolute; top: 100%; left: 0; right: 0; 
                max-height: 250px; overflow-y: auto; background: white; 
                border: 1px solid #bac8d6; border-radius: 6px; z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-top: 2px;">
        
        <div class="searchable-option clear-option" 
             data-value=""
             data-text=""
             style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0;
                    color: #627386; font-style: italic; transition: background 0.15s;"
             onmouseover="this.style.background='#f4f7fb'"
             onmouseout="this.style.background='white'"
             onclick="selectSearchableOption('{{ $id }}', '', '', this)">
            -- Clear Selection --
        </div>
        
        @foreach($options as $option)
            <div class="searchable-option" 
                 data-value="{{ $option['value'] }}"
                 data-text="{{ $option['text'] }}"
                 data-subtext="{{ $option['subtext'] ?? '' }}"
                 style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0;
                        transition: background 0.15s;"
                 onmouseover="this.style.background='#e8f3f2'"
                 onmouseout="this.style.background='white'"
                 onclick="selectSearchableOption('{{ $id }}', '{{ $option['value'] }}', '{{ $option['text'] }}', this)">
                <div style="font-weight: 600; color: #1b2635;">
                    {{ $option['text'] }}
                </div>
                @if(!empty($option['subtext']))
                    <div style="font-size: 11px; color: #627386; margin-top: 2px;">
                        {{ $option['subtext'] }}
                    </div>
                @endif
            </div>
        @endforeach
        
        <div class="no-results" style="display: none; padding: 15px; text-align: center; color: #627386;">
            No results found
        </div>
    </div>
    
    <input type="hidden" 
           name="{{ $name }}" 
           id="{{ $id }}" 
           value="{{ $selected }}"
           {{ $required ? 'required' : '' }}>
    
    <small style="color: #627386; margin-top: 4px; display: block;">
        <span id="{{ $id }}_count">{{ count($options) }}</span> options available · Type to search
    </small>
</div>

<style>
    .searchable-select-input:focus {
        outline: none;
        border-color: var(--accent, #1b7f79) !important;
        box-shadow: 0 0 0 2px rgba(27,127,121,0.1);
    }
    .searchable-select-dropdown::-webkit-scrollbar {
        width: 6px;
    }
    .searchable-select-dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .searchable-select-dropdown::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    .searchable-select-dropdown::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    .searchable-option.selected {
        background: #e8f3f2 !important;
        border-left: 3px solid #1b7f79;
    }
    .searchable-highlight {
        background: #fff3cd;
        padding: 1px 3px;
        border-radius: 2px;
    }
</style>

<script>
    // Store all instances
    if (!window.searchableInstances) {
        window.searchableInstances = {};
    }
    
    function showSearchableDropdown(id) {
        const dropdown = document.getElementById(id + '_dropdown');
        if (dropdown) {
            dropdown.style.display = 'block';
            filterSearchableOptions(id);
        }
    }
    
    function filterSearchableOptions(id) {
        const searchInput = document.getElementById(id + '_search');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const dropdown = document.getElementById(id + '_dropdown');
        const options = dropdown.querySelectorAll('.searchable-option:not(.clear-option)');
        const noResults = dropdown.querySelector('.no-results');
        const countSpan = document.getElementById(id + '_count');
        
        let visibleCount = 0;
        
        options.forEach(option => {
            const text = option.getAttribute('data-text').toLowerCase();
            const subtext = option.getAttribute('data-subtext').toLowerCase();
            
            if (searchTerm === '' || text.includes(searchTerm) || subtext.includes(searchTerm)) {
                option.style.display = 'block';
                visibleCount++;
                
                // Highlight matching text
                if (searchTerm !== '') {
                    const nameDiv = option.querySelector('div:first-child');
                    const originalText = option.getAttribute('data-text');
                    nameDiv.innerHTML = highlightSearchText(originalText, searchTerm);
                } else {
                    const nameDiv = option.querySelector('div:first-child');
                    nameDiv.textContent = option.getAttribute('data-text');
                }
            } else {
                option.style.display = 'none';
            }
        });
        
        // Update count
        if (countSpan) {
            countSpan.textContent = visibleCount;
        }
        
        // Show/hide no results
        if (noResults) {
            noResults.style.display = visibleCount === 0 && searchTerm !== '' ? 'block' : 'none';
            if (visibleCount === 0 && searchTerm !== '') {
                noResults.innerHTML = 'No results found for "<strong>' + searchTerm + '</strong>"';
            }
        }
    }
    
    function highlightSearchText(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span class="searchable-highlight">$1</span>');
    }
    
    function selectSearchableOption(id, value, text, element) {
        // Update hidden input
        document.getElementById(id).value = value;
        
        // Update search input
        document.getElementById(id + '_search').value = text;
        
        // Update visual selection
        const dropdown = document.getElementById(id + '_dropdown');
        dropdown.querySelectorAll('.searchable-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        element.classList.add('selected');
        
        // Hide dropdown
        dropdown.style.display = 'none';
        
        // Trigger change event
        const hiddenInput = document.getElementById(id);
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Call onSelect callback if exists
        if (window.searchableInstances[id] && window.searchableInstances[id].onSelect) {
            window.searchableInstances[id].onSelect(value, text);
        }
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('.searchable-select-wrapper').forEach(wrapper => {
            const id = wrapper.querySelector('input[type="hidden"]').id;
            const searchInput = document.getElementById(id + '_search');
            const dropdown = document.getElementById(id + '_dropdown');
            
            if (searchInput && dropdown) {
                if (!wrapper.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        const activeElement = document.activeElement;
        if (!activeElement || !activeElement.classList.contains('searchable-select-input')) return;
        
        const id = activeElement.id.replace('_search', '');
        const dropdown = document.getElementById(id + '_dropdown');
        
        if (!dropdown || dropdown.style.display === 'none') return;
        
        const visibleOptions = Array.from(dropdown.querySelectorAll('.searchable-option'))
            .filter(opt => opt.style.display !== 'none');
        
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            activeElement.blur();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const selected = dropdown.querySelector('.searchable-option.selected');
            if (selected) {
                const value = selected.getAttribute('data-value');
                const text = selected.getAttribute('data-text');
                selectSearchableOption(id, value, text, selected);
            }
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            
            const currentIndex = visibleOptions.findIndex(opt => opt.classList.contains('selected'));
            let nextIndex;
            
            if (e.key === 'ArrowDown') {
                nextIndex = currentIndex < visibleOptions.length - 1 ? currentIndex + 1 : 0;
            } else {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : visibleOptions.length - 1;
            }
            
            visibleOptions.forEach(opt => opt.classList.remove('selected'));
            if (visibleOptions[nextIndex]) {
                visibleOptions[nextIndex].classList.add('selected');
                visibleOptions[nextIndex].scrollIntoView({ block: 'nearest' });
            }
        }
    });
</script>
