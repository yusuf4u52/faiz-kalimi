'use strict';
 
const cacheName = 'fmb-kalimi-cache-2.2.25';
const startPage = 'https://kalimijamaatpoona.org/fmb/';
const offlinePage = 'https://kalimijamaatpoona.org/fmb/';
const filesToCache = [startPage, offlinePage];
const neverCacheUrls = [/\/users\/viewmenu.php/,/\/admin/];

// Install
self.addEventListener('install', function(e) {
	console.log('Service worker installation');
	e.waitUntil(
		caches.open(cacheName).then(function(cache) {
			console.log('Service worker caching dependencies');
			filesToCache.map(function(url) {
				return cache.add(url).catch(function (reason) {
					return console.log( String(reason) + ' ' + url);
				});
			});
		})
	);
});

// Activate
self.addEventListener('activate', function(e) {
	console.log('Service worker activation');
	e.waitUntil(
		caches.keys().then(function(keyList) {
			return Promise.all(keyList.map(function(key) {
				if ( key !== cacheName ) {
					console.log('Old cache removed', key);
					return caches.delete(key);
				}
			}));
		})
	);
	return self.clients.claim();
});

// Range Data Code
var fetchRangeData = function(event){
    var pos = Number(/^bytes\=(\d+)\-$/g.exec(event.request.headers.get('range'))[1]);
            console.log('Range request for', event.request.url, ', starting position:', pos);
            event.respondWith(
              caches.open(cacheName)
              .then(function(cache) {
                return cache.match(event.request.url);
              }).then(function(res) {
                if (!res) {
                  return fetch(event.request)
                  .then(res => {
                    return res.arrayBuffer();
                  });
                }
                return res.arrayBuffer();
              }).then(function(ab) {
                return new Response(
                  ab.slice(pos),
                  {
                    status: 206,
                    statusText: 'Partial Content',
                    headers: [
                      // ['Content-Type', 'video/webm'],
                      ['Content-Range', 'bytes ' + pos + '-' +
                        (ab.byteLength - 1) + '/' + ab.byteLength]]
                  });
              }));
}

// Fetch
self.addEventListener('fetch', function(e) {
	
	// Return if the current request url is in the never cache list
	if ( ! neverCacheUrls.every(checkNeverCacheList, e.request.url) ) {
	  console.log( 'Current request is excluded from cache.' );
	  return;
	}
	
	// Return if request url protocal isn't http or https
	if ( ! e.request.url.match(/^(http|https):\/\//i) )
		return;
	
    	// Return if request url is from an external domain.
	if ( new URL(e.request.url).origin !== location.origin )
		return;
    
			// For POST requests, do not use the cache. Serve offline page if offline.
			if ( e.request.method !== 'GET' ) {
				e.respondWith(
					fetch(e.request).catch( function() {
						        return caches.match(offlinePage);
					})
				);
				return;
			}
			
			// For Range Headers
			if (e.request.headers.has('range')) {
				return;
			}
			// Revving strategy
			if ( (e.request.mode === 'navigate' || e.request.mode === 'cors') && navigator.onLine ) {
				e.respondWith(
					fetch(e.request).then(function(response) {
						return caches.open(cacheName).then(function(cache) {
							cache.put(e.request, response.clone());
							return response;
						});  
					}).catch(function(){
						// If the network is unavailable, get
						return cache.match(e.request.url);
					})
				);
				return;
			}

			//strategy_replace_start
			e.respondWith(
				caches.match(e.request).then(function(response) {
					return response || fetch(e.request).then(function(response) {
						return caches.open(cacheName).then(function(cache) {
							cache.put(e.request, response.clone());
							return response; 
						});  
					});
				}).catch(function() {
					return caches.match(offlinePage);
				})
			);
			//strategy_replace_end


});

// Check if current url is in the neverCacheUrls list
function checkNeverCacheList(url) {
	if ( this.match(url) ) {
		return false;
	}
	return true;
}
importScripts("https://storage.googleapis.com/workbox-cdn/releases/6.0.2/workbox-sw.js");
	            if(workbox.googleAnalytics){
                  try{
                    workbox.googleAnalytics.initialize();
                  } catch (e){ console.log(e.message); }
                }