<?php
/**
 * Tulima Solar - Admin Global Search
 * Search across all content types
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'search';
$pageTitle = 'Search';

// Get search parameters
$query = sanitizeSearchQuery($_GET['q'] ?? '');
$selectedType = $_GET['type'] ?? '';
$page = max((int)($_GET['page'] ?? 1), 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Define searchable content types
$contentTypes = [
    'pages' => ['label' => 'Pages', 'icon' => 'file-text', 'color' => '#3b82f6'],
    'products' => ['label' => 'Products', 'icon' => 'package', 'color' => '#10b981'],
    'sections' => ['label' => 'Sections', 'icon' => 'layers', 'color' => '#8b5cf6'],
    'testimonials' => ['label' => 'Testimonials', 'icon' => 'message-square', 'color' => '#f59e0b'],
    'partners' => ['label' => 'Partners', 'icon' => 'users', 'color' => '#ef4444'],
    'contacts' => ['label' => 'Contact Submissions', 'icon' => 'mail', 'color' => '#6366f1'],
];

// Get types to search
$typesToSearch = $selectedType && isset($contentTypes[$selectedType]) 
    ? [$selectedType] 
    : array_keys($contentTypes);

// Perform search
$results = [];
$totalResults = 0;
$searchPerformed = false;

if (strlen($query) >= 2) {
    $searchPerformed = true;
    $results = globalSearch($pdo, $query, $typesToSearch, $limit, true);
    $totalResults = getTotalSearchResults($results);
    
    // Log the search
    $userId = $_SESSION['admin_user_id'] ?? null;
    logSearch($pdo, $query, $totalResults, $userId);
}

// Get recent searches
$recentSearches = getRecentSearches($pdo, $_SESSION['admin_user_id'] ?? null, session_id(), 5);

// Get popular searches
$popularSearches = getPopularSearches($pdo, 10);

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
    <!-- Page Header -->
    <div class="admin-page__header">
        <div class="admin-page__header-content">
            <h1 class="admin-page__title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Global Search
            </h1>
            <p class="admin-page__subtitle">Search across all content types</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="search-container">
        <form method="GET" action="" class="search-form">
            <div class="search-input-wrapper">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input 
                    type="text" 
                    name="q" 
                    value="<?php echo e($query); ?>" 
                    placeholder="Search pages, products, testimonials..." 
                    class="search-input"
                    id="searchInput"
                    autocomplete="off"
                >
                <?php if ($selectedType): ?>
                    <input type="hidden" name="type" value="<?php echo e($selectedType); ?>">
                <?php endif; ?>
                <?php if ($query): ?>
                    <a href="?q=" class="search-clear" title="Clear search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <button type="submit" class="search-button">Search</button>
        </form>

        <!-- Autocomplete Dropdown -->
        <div class="search-autocomplete" id="searchAutocomplete" style="display: none;">
            <div class="autocomplete-section">
                <div class="autocomplete-label">Suggestions</div>
                <div id="autocompleteSuggestions"></div>
            </div>
            <div class="autocomplete-section">
                <div class="autocomplete-label">Quick Results</div>
                <div id="autocompleteResults"></div>
            </div>
        </div>
    </div>

    <div class="search-layout">
        <!-- Sidebar Filters -->
        <aside class="search-sidebar">
            <div class="search-filters">
                <h3 class="search-filters__title">Filter by Type</h3>
                <ul class="search-filters__list">
                    <li>
                        <a href="?q=<?php echo e($query); ?>" class="search-filter-item <?php echo !$selectedType ? 'active' : ''; ?>">
                            <span class="search-filter-item__label">All Types</span>
                            <span class="search-filter-item__count"><?php echo $totalResults; ?></span>
                        </a>
                    </li>
                    <?php foreach ($contentTypes as $type => $info): ?>
                        <?php $typeCount = isset($results[$type]) ? count($results[$type]) : 0; ?>
                        <li>
                            <a href="?q=<?php echo e($query); ?>&type=<?php echo e($type); ?>" class="search-filter-item <?php echo $selectedType === $type ? 'active' : ''; ?>">
                                <span class="search-filter-item__icon" style="color: <?php echo $info['color']; ?>">
                                    <?php echo getTypeIcon($info['icon']); ?>
                                </span>
                                <span class="search-filter-item__label"><?php echo e($info['label']); ?></span>
                                <span class="search-filter-item__count"><?php echo $typeCount; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if (!empty($recentSearches)): ?>
            <div class="search-sidebar__section">
                <h3 class="search-filters__title">Recent Searches</h3>
                <ul class="recent-searches">
                    <?php foreach ($recentSearches as $search): ?>
                        <li>
                            <a href="?q=<?php echo e($search['query']); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <?php echo e($search['query']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($popularSearches) && !$query): ?>
            <div class="search-sidebar__section">
                <h3 class="search-filters__title">Popular Searches</h3>
                <ul class="popular-searches">
                    <?php foreach ($popularSearches as $search): ?>
                        <li>
                            <a href="?q=<?php echo e($search['query']); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <?php echo e($search['query']); ?>
                                <span class="search-count"><?php echo (int)$search['count']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Results -->
        <main class="search-results">
            <?php if (!$searchPerformed): ?>
                <!-- Empty State -->
                <div class="search-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <h2>Search Everything</h2>
                    <p>Find pages, products, testimonials, partners, and more.</p>
                    <p class="search-empty__hint">Start typing to see suggestions</p>
                </div>

            <?php elseif ($totalResults === 0): ?>
                <!-- No Results -->
                <div class="search-empty search-empty--no-results">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <h2>No Results Found</h2>
                    <p>We couldn't find anything matching "<?php echo e($query); ?>"</p>
                    <div class="search-empty__suggestions">
                        <p>Try:</p>
                        <ul>
                            <li>Checking your spelling</li>
                            <li>Using more general terms</li>
                            <li>Searching with fewer keywords</li>
                        </ul>
                    </div>
                </div>

            <?php else: ?>
                <!-- Results Header -->
                <div class="search-results__header">
                    <p class="search-results__count">
                        Found <strong><?php echo $totalResults; ?></strong> result<?php echo $totalResults !== 1 ? 's' : ''; ?> 
                        for "<strong><?php echo e($query); ?></strong>"
                    </p>
                </div>

                <!-- Results by Type -->
                <?php foreach ($results as $type => $items): ?>
                    <?php if (!empty($items)): ?>
                        <div class="search-results__section" id="results-<?php echo e($type); ?>">
                            <div class="search-results__section-header">
                                <h2 class="search-results__section-title">
                                    <span class="search-results__section-icon" style="color: <?php echo $contentTypes[$type]['color']; ?>">
                                        <?php echo getTypeIcon($contentTypes[$type]['icon']); ?>
                                    </span>
                                    <?php echo e($contentTypes[$type]['label']); ?>
                                </h2>
                                <span class="search-results__section-count"><?php echo count($items); ?> result<?php echo count($items) !== 1 ? 's' : ''; ?></span>
                            </div>
                            
                            <div class="search-results__list">
                                <?php foreach ($items as $item): ?>
                                    <a href="<?php echo e($item['edit_url']); ?>" class="search-result-item">
                                        <div class="search-result-item__content">
                                            <h3 class="search-result-item__title">
                                                <?php echo $item['snippet']; ?>
                                            </h3>
                                            <?php if (!empty($item['category_name'])): ?>
                                                <span class="search-result-item__meta">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                                    </svg>
                                                    <?php echo e($item['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($type === 'contacts' && !empty($item['email'])): ?>
                                                <span class="search-result-item__meta">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                        <polyline points="22,6 12,13 2,6"/>
                                                    </svg>
                                                    <?php echo e($item['email']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="search-result-item__actions">
                                            <?php if (isset($item['is_active'])): ?>
                                                <span class="status-badge status-badge--<?php echo $item['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="search-result-item__relevance">
                                                <?php echo (int)$item['relevance']; ?>% match
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalResults > $limit): ?>
                    <div class="search-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?php echo e($query); ?>&type=<?php echo e($selectedType); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn pagination-btn--prev">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo ceil($totalResults / $limit); ?>
                        </span>
                        
                        <?php if ($totalResults > ($page * $limit)): ?>
                            <a href="?q=<?php echo e($query); ?>&type=<?php echo e($selectedType); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn pagination-btn--next">
                                Next
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
/* Search Container */
.search-container {
    position: relative;
    margin-bottom: 2rem;
}

.search-form {
    display: flex;
    gap: 1rem;
    align-items: stretch;
}

.search-input-wrapper {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1rem;
    width: 20px;
    height: 20px;
    color: var(--text-muted);
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 0.875rem 2.5rem;
    padding-left: 3rem;
    font-size: 1.125rem;
    border: 2px solid var(--border-color);
    border-radius: 0.5rem;
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-clear {
    position: absolute;
    right: 0.75rem;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    border-radius: 50%;
    transition: background-color 0.2s, color 0.2s;
}

.search-clear:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.search-button {
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-button:hover {
    background: var(--color-primary-dark);
}

/* Autocomplete */
.search-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 0.5rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
}

.autocomplete-section {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.autocomplete-section:last-child {
    border-bottom: none;
}

.autocomplete-label {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

.autocomplete-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background-color 0.15s;
}

.autocomplete-item:hover,
.autocomplete-item--selected {
    background: var(--bg-secondary);
}

.autocomplete-item__icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    background: var(--bg-secondary);
}

.autocomplete-item__content {
    flex: 1;
    min-width: 0;
}

.autocomplete-item__title {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.autocomplete-item__type {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* Search Layout */
.search-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

@media (max-width: 768px) {
    .search-layout {
        grid-template-columns: 1fr;
    }
}

/* Search Sidebar */
.search-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.search-filters {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    padding: 1rem;
}

.search-filters__title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
}

.search-filters__list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.search-filter-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    margin: 0.25rem 0;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 0.375rem;
    transition: background-color 0.15s, color 0.15s;
}

.search-filter-item:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.search-filter-item.active {
    background: var(--color-primary);
    color: white;
}

.search-filter-item__icon {
    width: 18px;
    height: 18px;
}

.search-filter-item__label {
    flex: 1;
}

.search-filter-item__count {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    background: var(--bg-secondary);
    border-radius: 1rem;
}

.search-filter-item.active .search-filter-item__count {
    background: rgba(255, 255, 255, 0.2);
}

/* Recent/Popular Searches */
.recent-searches,
.popular-searches {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recent-searches li,
.popular-searches li {
    margin: 0.25rem 0;
}

.recent-searches a,
.popular-searches a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: background-color 0.15s, color 0.15s;
}

.recent-searches a:hover,
.popular-searches a:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.recent-searches svg,
.popular-searches svg {
    width: 14px;
    height: 14px;
    opacity: 0.5;
}

.search-count {
    margin-left: auto;
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* Search Results */
.search-results {
    min-height: 400px;
}

.search-results__header {
    margin-bottom: 1.5rem;
}

.search-results__count {
    color: var(--text-secondary);
}

.search-results__section {
    margin-bottom: 2rem;
}

.search-results__section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--border-color);
}

.search-results__section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

.search-results__section-icon {
    width: 20px;
    height: 20px;
}

.search-results__section-count {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.search-results__list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Search Result Item */
.search-result-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.search-result-item:hover {
    border-color: var(--color-primary);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.search-result-item__content {
    flex: 1;
    min-width: 0;
}

.search-result-item__title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-primary);
    margin: 0 0 0.25rem;
}

.search-result-item__title :is(mark, .search-highlight) {
    background: rgba(251, 191, 36, 0.3);
    color: inherit;
    padding: 0 0.125rem;
    border-radius: 0.125rem;
}

.search-result-item__meta {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.search-result-item__meta svg {
    width: 12px;
    height: 12px;
}

.search-result-item__actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.search-result-item__relevance {
    font-size: 0.75rem;
    color: var(--text-muted);
    padding: 0.25rem 0.5rem;
    background: var(--bg-secondary);
    border-radius: 0.25rem;
}

/* Status Badge */
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
}

.status-badge--active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.status-badge--inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

/* Empty State */
.search-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
}

.search-empty svg {
    width: 64px;
    height: 64px;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}

.search-empty h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.search-empty p {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.search-empty__hint {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.search-empty--no-results svg {
    color: #f59e0b;
}

.search-empty__suggestions {
    margin-top: 1.5rem;
    text-align: left;
    display: inline-block;
}

.search-empty__suggestions p {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.search-empty__suggestions ul {
    list-style: disc;
    padding-left: 1.5rem;
    color: var(--text-secondary);
}

.search-empty__suggestions li {
    margin: 0.25rem 0;
}

/* Pagination */
.search-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1.5rem 0;
    border-top: 1px solid var(--border-color);
    margin-top: 2rem;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: border-color 0.15s, background-color 0.15s;
}

.pagination-btn:hover {
    border-color: var(--color-primary);
    background: var(--bg-secondary);
}

.pagination-btn svg {
    width: 16px;
    height: 16px;
}

.pagination-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* Dark mode adjustments */
.dark .search-autocomplete {
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
}

.dark .search-highlight {
    background: rgba(251, 191, 36, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const autocomplete = document.getElementById('searchAutocomplete');
    const suggestionsContainer = document.getElementById('autocompleteSuggestions');
    const resultsContainer = document.getElementById('autocompleteResults');
    
    let debounceTimer;
    let selectedIndex = -1;
    let currentItems = [];
    
    // Debounced search
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            autocomplete.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetchInstantResults(query);
        }, 300);
    });
    
    // Fetch instant results
    function fetchInstantResults(query) {
        fetch(`api/search.php?action=instant&q=${encodeURIComponent(query)}&limit=5`)
            .then(response => response.json())
            .then(data => {
                currentItems = [];
                selectedIndex = -1;
                
                // Render suggestions
                if (data.suggestions && data.suggestions.length > 0) {
                    suggestionsContainer.innerHTML = data.suggestions.map(s => `
                        <a href="?q=${encodeURIComponent(s.text)}" class="autocomplete-item" data-type="suggestion">
                            <div class="autocomplete-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                            <div class="autocomplete-item__content">
                                <div class="autocomplete-item__title">${escapeHtml(s.text)}</div>
                                <div class="autocomplete-item__type">${s.type}</div>
                            </div>
                        </a>
                    `).join('');
                    currentItems.push(...data.suggestions.map(s => ({ type: 'suggestion', url: `?q=${encodeURIComponent(s.text)}` })));
                } else {
                    suggestionsContainer.innerHTML = '<div class="autocomplete-item" style="color: var(--text-muted);">No suggestions</div>';
                }
                
                // Render quick results
                let resultsHtml = '';
                if (data.results) {
                    for (const [type, items] of Object.entries(data.results)) {
                        items.forEach(item => {
                            resultsHtml += `
                                <a href="${escapeHtml(item.edit_url)}" class="autocomplete-item" data-type="result">
                                    <div class="autocomplete-item__icon" style="color: ${getTypeColor(type)}">
                                        ${getTypeIconSvg(type)}
                                    </div>
                                    <div class="autocomplete-item__content">
                                        <div class="autocomplete-item__title">${item.snippet}</div>
                                        <div class="autocomplete-item__type">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                                    </div>
                                </a>
                            `;
                            currentItems.push({ type: 'result', url: item.edit_url });
                        });
                    }
                }
                
                if (resultsHtml) {
                    resultsContainer.innerHTML = resultsHtml;
                } else {
                    resultsContainer.innerHTML = '<div class="autocomplete-item" style="color: var(--text-muted);">No results</div>';
                }
                
                autocomplete.style.display = 'block';
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }
    
    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = autocomplete.querySelectorAll('.autocomplete-item[href]');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            if (items[selectedIndex]) {
                items[selectedIndex].click();
            }
        } else if (e.key === 'Escape') {
            autocomplete.style.display = 'none';
        }
    });
    
    function updateSelection(items) {
        items.forEach((item, index) => {
            item.classList.toggle('autocomplete-item--selected', index === selectedIndex);
        });
        
        if (selectedIndex >= 0 && items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }
    
    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocomplete.contains(e.target)) {
            autocomplete.style.display = 'none';
        }
    });
    
    // Helper functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getTypeColor(type) {
        const colors = {
            pages: '#3b82f6',
            products: '#10b981',
            sections: '#8b5cf6',
            testimonials: '#f59e0b',
            partners: '#ef4444',
            contacts: '#6366f1'
        };
        return colors[type] || '#6b7280';
    }
    
    function getTypeIconSvg(type) {
        const icons = {
            pages: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            products: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>',
            sections: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
            testimonials: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            partners: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
            contacts: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'
        };
        return icons[type] || '';
    }
});
</script>

<?php
/**
 * Helper function to get SVG icon by name
 */
function getTypeIcon($name) {
    $icons = [
        'file-text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'package' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
        'message-square' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    ];
    return $icons[$name] ?? '';
}

require_once __DIR__ . '/includes/footer.php';
?>
