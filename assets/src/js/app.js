import 'bootstrap';

import {setupCookies} from './cookies';
import {setupHeader} from './header';
import {setupLoadMore} from './load_more';
import {setupPDFIcon} from './pdf_icon';
import {setupSearch} from './search';
import {setupExternalLinks} from './external_links';
import {setupListingPages} from './listing_pages';
import {setupQueryLoopCarousel} from './query_loop_carousel';

function requireAll(r) {
  r.keys().forEach(r);
}

requireAll(require.context('../images/icons/', true, /\.svg$/));

setupCookies();
setupHeader();
setupLoadMore();
setupPDFIcon();
setupSearch();
setupExternalLinks();
setupQueryLoopCarousel();

window.addEventListener('load', () => {
  if (document.getElementById('listing-page-root')) {
    setupListingPages();
  }
});
