# Phase 9: Global Search Functionality

## Overview

This phase implements a comprehensive global search system for the Tulima Solar CMS, enabling users to search across multiple content types in both the admin panel and the public-facing website.

## Implementation Date

**Completed:** February 2026

## Features Implemented

### 1. Search Helper Functions (`includes/functions.php`)

Added a complete suite of search helper functions:

| Function | Description |
|----------|-------------|
| `globalSearch()` | Main search function that searches across multiple content types |
| `searchPages()` | Search pages by title and content |
| `searchProducts()` | Search products by name, description, and specifications |
| `searchSections()` | Search page sections by title and content |
| `searchTestimonials()` | Search testimonials by client name and testimonial text |
| `searchPartners()` | Search partners by name |
| `searchContactSubmissions()` | Search contact submissions (admin only) |
| `generateSearchSnippet()` | Generate text snippet with context around search match |
| `highlightSearchTerms()` | Highlight matched search terms in text |
| `logSearch()` | Log searches for analytics |
| `getSearchSuggestions()` | Get autocomplete suggestions |
| `getPopularSearches()` | Get popular search terms |
| `getRecentSearches()` | Get recent searches for user/session |
| `getTotalSearchResults()` | Count total results across all types |
| `sanitizeSearchQuery()` | Sanitize search query for security |
| `parseSearchQuery()` | Parse query for exact phrase matching |

### 2. Search API Endpoint (`admin/api/search.php`)

RESTful API endpoint with the following actions:

| Action | Method | Description |
|--------|--------|-------------|
| `search` | GET | Full search with pagination |
| `suggestions` | GET | Autocomplete suggestions |
| `recent` | GET | Recent searches for user |
| `popular` | GET | Popular search terms |
| `instant` | GET | Quick preview results for dropdown |

**Parameters:**
- `q` - Search query (required for search/suggestions/instant)
- `types` - Comma-separated content types to search
- `limit` - Maximum results per type (default: 50, max: 100)
- `page` - Page number for pagination
- `days` - Days to look back for popular searches

**Rate Limiting:**
- 30 requests per minute per session

**Example Response:**
```json
{
  "query": "solar",
  "results": {
    "products": [...],
    "pages": [...],
    "testimonials": [...]
  },
  "total": 15,
  "page": 1,
  "limit": 50,
  "has_more": false
}
```

### 3. Admin Search Page (`admin/search.php`)

Full-featured search interface for administrators:

**Features:**
- Real-time autocomplete dropdown
- Filter results by content type
- Grouped results with type icons
- Relevance scoring display
- Status badges (Active/Inactive)
- Recent searches sidebar
- Popular searches display
- Pagination support
- Keyboard navigation (arrow keys, enter, escape)
- Keyboard shortcut: `Ctrl/Cmd + K` to focus search

**UI Components:**
- Search input with clear button
- Autocomplete dropdown with suggestions and quick results
- Sidebar filters with result counts
- Result cards with snippets and metadata
- Empty state with suggestions
- No results state with helpful tips

### 4. Frontend Site Search (`pages/search.php`)

Public-facing search for website visitors:

**Features:**
- Clean, branded search interface
- Search only published content
- Products displayed in grid with thumbnails
- Pages and testimonials in list format
- Popular searches on empty state
- Pagination for large result sets
- Mobile-responsive design

**Content Types Searched:**
- Products (name, description, specifications)
- Pages (title, meta description)
- Testimonials (client name, testimonial)

### 5. Header Search Integration (`admin/includes/header.php`)

Enhanced global search in admin header:

**Features:**
- Expanding search input
- Live autocomplete dropdown
- Quick results preview
- Loading indicator
- Keyboard navigation
- Click-outside-to-close behavior
- Type-specific icons and colors

## Database Schema

### Search Logs Table

```sql
CREATE TABLE search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    user_id INT NULL,
    session_id VARCHAR(64) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_query (query),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The table is automatically created on first search if it doesn't exist.

## Search Logic

### Relevance Scoring

Results are scored based on where the match occurs:

| Match Location | Score |
|----------------|-------|
| Title/Name exact match | 100 |
| Title/Name partial match | 100 |
| Short description | 60 |
| Email (contacts) | 90 |
| Subject (contacts) | 70 |
| Content/Message | 50 |
| Specifications | 30 |
| Meta description | 50 |

### Query Processing

1. **Sanitization:** Remove special characters that could be used for SQL injection
2. **Minimum Length:** 2 characters required
3. **Case Insensitive:** All searches are case-insensitive
4. **Partial Matching:** Uses LIKE with wildcards for partial word matching
5. **Exact Phrases:** Quoted strings are parsed for exact matching (future enhancement)

### Snippet Generation

- Maximum 150 characters
- Shows context around first match
- Adds ellipsis for truncated text
- Highlights matched terms with `<mark>` tags

## Security Considerations

1. **SQL Injection Prevention:** All queries use prepared statements
2. **Input Sanitization:** `sanitizeSearchQuery()` removes dangerous characters
3. **Rate Limiting:** 30 requests per minute prevents abuse
4. **Authentication Required:** Admin search requires authenticated session
5. **Content Filtering:** Frontend search only returns published content

## Performance Optimizations

1. **Debounced Autocomplete:** 300ms delay prevents excessive API calls
2. **Result Limiting:** Maximum 50 results per type
3. **Pagination:** Large result sets are paginated
4. **Index Recommendations:** Add indexes on frequently searched columns

### Recommended Indexes

```sql
-- For better search performance
ALTER TABLE products ADD INDEX idx_search (name, short_description);
ALTER TABLE pages ADD INDEX idx_search (title, meta_description);
ALTER TABLE testimonials ADD INDEX idx_search (client_name, testimonial);
ALTER TABLE partners ADD INDEX idx_search (name);

-- For FULLTEXT search (optional, for large datasets)
ALTER TABLE products ADD FULLTEXT INDEX idx_fulltext (name, short_description, specifications);
ALTER TABLE pages ADD FULLTEXT INDEX idx_fulltext (title, meta_description);
```

## API Usage Examples

### JavaScript (Fetch API)

```javascript
// Full search
fetch('/admin/api/search.php?action=search&q=solar&limit=10')
  .then(response => response.json())
  .then(data => console.log(data.results));

// Get suggestions
fetch('/admin/api/search.php?action=suggestions&q=so')
  .then(response => response.json())
  .then(data => console.log(data.suggestions));

// Instant search for dropdown
fetch('/admin/api/search.php?action=instant&q=solar')
  .then(response => response.json())
  .then(data => {
    console.log('Suggestions:', data.suggestions);
    console.log('Quick Results:', data.results);
  });
```

### PHP

```php
require_once 'includes/functions.php';

$pdo = getDb();

// Global search
$results = globalSearch($pdo, 'solar pump', [], 20, true);

// Search specific types
$products = searchProducts($pdo, 'pump', 10, false);

// Get suggestions
$suggestions = getSearchSuggestions($pdo, 'sol', 5);

// Log a search
logSearch($pdo, 'solar pump', 15, $userId);

// Get popular searches
$popular = getPopularSearches($pdo, 10, 30);
```

## File Structure

```
├── admin/
│   ├── api/
│   │   └── search.php          # Search API endpoint
│   ├── includes/
│   │   └── header.php          # Modified with search integration
│   └── search.php              # Admin search results page
├── pages/
│   └── search.php              # Frontend search page
├── includes/
│   └── functions.php           # Search helper functions added
└── docs/
    └── phase9-global-search.md # This documentation
```

## Configuration Options

Future configuration options that could be added to `site_settings`:

| Setting | Description | Default |
|---------|-------------|---------|
| `search_min_length` | Minimum characters for search | 2 |
| `search_max_results` | Maximum results per type | 50 |
| `search_rate_limit` | Requests per minute | 30 |
| `search_log_enabled` | Enable search logging | true |
| `search_log_retention` | Days to keep logs | 90 |
| `search_highlight_results` | Highlight matched terms | true |

## Testing Checklist

- [ ] Admin search returns results from all content types
- [ ] Frontend search only returns published content
- [ ] Autocomplete suggestions appear after 2 characters
- [ ] Keyboard navigation works in dropdown
- [ ] Search logging records queries
- [ ] Popular searches display correctly
- [ ] Recent searches show for logged-in users
- [ ] Pagination works for large result sets
- [ ] Rate limiting prevents excessive requests
- [ ] Mobile responsive design works
- [ ] Dark mode styling is correct
- [ ] No results state displays suggestions
- [ ] Search clears properly with X button
- [ ] Ctrl/Cmd+K focuses search input

## Future Enhancements

1. **FULLTEXT Search:** Implement MySQL FULLTEXT indexes for better relevance
2. **Search Analytics Dashboard:** Visualize search trends in admin
3. **Synonym Support:** Map related terms (e.g., "pump" → "water pump")
4. **Search Filters:** Date range, category, status filters
5. **Voice Search:** Add voice input capability
6. **Search Export:** Export search results as CSV
7. **AI Suggestions:** Use ML for smarter suggestions
8. **Fuzzy Matching:** Handle typos and misspellings
9. **Search API for External Use:** Token-based API for external applications

## Troubleshooting

### Common Issues

1. **No results found:**
   - Check minimum query length (2 characters)
   - Verify content exists in database
   - Check is_active status for frontend search

2. **Autocomplete not working:**
   - Check browser console for JavaScript errors
   - Verify API endpoint is accessible
   - Check rate limiting hasn't been triggered

3. **Search logging not working:**
   - Verify search_logs table exists
   - Check database permissions
   - Review error logs

4. **Slow search performance:**
   - Add recommended database indexes
   - Reduce result limit
   - Enable query caching

## Support

For issues or questions about the global search functionality:

1. Check this documentation
2. Review the code comments in source files
3. Check browser console for JavaScript errors
4. Review server error logs
5. Contact the development team

---

**Phase 9 Status:** ✅ Complete

**Next Phase:** Phase 10 - Advanced Analytics Dashboard
