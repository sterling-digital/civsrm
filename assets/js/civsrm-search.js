// CivsRM Search Functionality
// This script provides a search functionality for CivsRM items, allowing users to search by keywords
document.addEventListener('DOMContentLoaded', () => {

  const searchInput = document.getElementById('civsrm-search');
  const searchBtn = document.getElementById('search-btn');
  const resetBtn = document.getElementById('reset-btn');
  const filterBtn = document.getElementById('filter-btn');
  const filterPanel = document.getElementById('filter-panel');
  const clearFiltersBtn = document.getElementById('clear-filters-btn');
  const container = document.querySelector('.civsrm-container');
  const noResultsMsg = document.getElementById('no-results');
  const items = Array.from(document.querySelectorAll('.civsrm-item'));
  const categoryGroups = Array.from(document.querySelectorAll('.civsrm-category-group'));
  const ciColumns = Array.from(document.querySelectorAll('.ci-items'));
  const rmColumns = Array.from(document.querySelectorAll('.rm-items'));
  const filterCheckboxes = Array.from(document.querySelectorAll('.filter-checkbox input[type="checkbox"]'));

  const searchData = items.map(item => {
    const content = item.querySelector('.description').textContent.toLowerCase();
    return {
      element: item,
      content: content
    };
  });

  const marker = new Mark(container);

  function clearHighlights() {
    marker.unmark();
  }

  function highlight(term) {
    marker.mark(term, { separateWordSearch: false });
  }

  function showAllItems() {
    // Show all items
    items.forEach(item => item.style.display = '');
    
    // Show all category groups
    categoryGroups.forEach(group => group.style.display = '');
    
    // Show all columns
    ciColumns.forEach(column => column.style.display = '');
    rmColumns.forEach(column => column.style.display = '');
    
    // Hide no results message
    noResultsMsg.style.display = 'none';
    
    // Clear highlights
    clearHighlights();
  }

  function hideEmptyContainers() {
    categoryGroups.forEach(group => {
      const groupItems = group.querySelectorAll('.civsrm-item');
      const visibleGroupItems = Array.from(groupItems).filter(item => 
        item.style.display !== 'none'
      );
      
      if (visibleGroupItems.length === 0) {
        group.style.display = 'none';
      } else {
        group.style.display = '';
        
        // Check CI column
        const ciItems = group.querySelectorAll('.ci-items .civsrm-item');
        const visibleCiItems = Array.from(ciItems).filter(item => 
          item.style.display !== 'none'
        );
        const ciColumn = group.querySelector('.ci-items');
        ciColumn.style.display = visibleCiItems.length > 0 ? '' : 'none';
        
        // Check RM column
        const rmItems = group.querySelectorAll('.rm-items .civsrm-item');
        const visibleRmItems = Array.from(rmItems).filter(item => 
          item.style.display !== 'none'
        );
        const rmColumn = group.querySelector('.rm-items');
        rmColumn.style.display = visibleRmItems.length > 0 ? '' : 'none';
      }
    });
  }

  function customSearch(searchTerm) {
    const results = [];
    const words = searchTerm.split(/\s+/).filter(word => word.length > 0);
    
    searchData.forEach(item => {
      const content = item.content;
      let score = 0;
      
      // Exact phrase match (highest score)
      if (content.includes(searchTerm)) {
        score += 100;
      }
      
      // All words present (medium score)
      const allWordsPresent = words.every(word => content.includes(word));
      if (allWordsPresent && words.length > 1) {
        score += 50;
      }
      
      // Individual word matches (lower score)
      words.forEach(word => {
        if (content.includes(word)) {
          score += 10;
        }
      });
      
      // Only include items with meaningful matches
      if (score >= 10) {
        results.push({
          element: item.element,
          score: score
        });
      }
    });
    
    // Sort by score (highest first)
    return results.sort((a, b) => b.score - a.score).map(result => result.element);
  }

  function getSelectedCategories() {
    return filterCheckboxes
      .filter(checkbox => checkbox.checked)
      .map(checkbox => checkbox.dataset.category);
  }

  function applyFilters() {
    const selectedCategories = getSelectedCategories();
    const searchTerm = searchInput.value.trim().toLowerCase();
    
    // If no categories are selected, show all content (no filter applied)
    if (selectedCategories.length === 0) {
      categoryGroups.forEach(group => group.style.display = '');
      items.forEach(item => item.style.display = '');
      ciColumns.forEach(column => column.style.display = '');
      rmColumns.forEach(column => column.style.display = '');
      noResultsMsg.style.display = 'none';
      clearHighlights();
      
      // If there's a search term, apply search to all content
      if (searchTerm) {
        performSearch(searchTerm);
      }
      
      updateFilterButtonState();
      return;
    }
    
    // Show/hide category groups based on selected filters
    categoryGroups.forEach(group => {
      const categoryName = group.querySelector('.civsrm-category-name').id;
      const isVisible = selectedCategories.includes(categoryName);
      group.style.display = isVisible ? '' : 'none';
    });
    
    // If there's a search term, apply search within filtered results
    if (searchTerm) {
      performSearch(searchTerm);
    } else {
      // Show all items within visible categories
      items.forEach(item => item.style.display = '');
      ciColumns.forEach(column => column.style.display = '');
      rmColumns.forEach(column => column.style.display = '');
      noResultsMsg.style.display = 'none';
      clearHighlights();
    }
    
    // Update filter button state
    updateFilterButtonState();
  }

  function updateFilterButtonState() {
    const totalCategories = filterCheckboxes.length;
    const selectedCategories = getSelectedCategories().length;
    
    // Show active state when some (but not all or none) categories are selected
    if (selectedCategories > 0 && selectedCategories < totalCategories) {
      filterBtn.classList.add('active');
    } else {
      filterBtn.classList.remove('active');
    }
  }

  function performSearch(term) {
    clearHighlights();

    if (!term) {
      // If no search term, just apply current filters
      applyFilters();
      return;
    }

    const results = customSearch(term);
    const selectedCategories = getSelectedCategories();

    // Hide/show items based on search results AND filter selection
    items.forEach(item => {
      const categoryGroup = item.closest('.civsrm-category-group');
      const categoryName = categoryGroup.querySelector('.civsrm-category-name').id;
      const isCategorySelected = selectedCategories.length === 0 || selectedCategories.includes(categoryName);
      const isSearchMatch = results.includes(item);
      
      item.style.display = (isCategorySelected && isSearchMatch) ? '' : 'none';
    });

    // Hide empty containers
    hideEmptyContainers();

    // Show/hide no results message
    const visibleItems = items.filter(item => item.style.display !== 'none');
    if (visibleItems.length === 0) {
      noResultsMsg.style.display = 'block';
    } else {
      noResultsMsg.style.display = 'none';
      highlight(term);
    }
  }

  // Search button event
  searchBtn.addEventListener('click', () => {
    const searchTerm = searchInput.value.trim().toLowerCase();
    performSearch(searchTerm);
  });

  // Reset button event
  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    // Reset all filters to unchecked state (no filter applied)
    filterCheckboxes.forEach(checkbox => checkbox.checked = false);
    filterBtn.classList.remove('active');
    // Hide filter panel
    filterPanel.style.display = 'none';
    showAllItems();
  });

  // Filter button event
  filterBtn.addEventListener('click', () => {
    if (filterPanel.style.display === 'none' || filterPanel.style.display === '') {
      filterPanel.style.display = 'block';
    } else {
      filterPanel.style.display = 'none';
    }
  });

  // Clear filters button event
  clearFiltersBtn.addEventListener('click', () => {
    filterCheckboxes.forEach(checkbox => checkbox.checked = false);
    filterBtn.classList.remove('active');
    applyFilters();
  });

  // Filter checkbox events
  filterCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      applyFilters();
    });
  });

  // Allow Enter key to trigger search
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      const searchTerm = searchInput.value.trim().toLowerCase();
      performSearch(searchTerm);
    }
  });

  // Initialize filter button state
  updateFilterButtonState();

});

console.log('1052');