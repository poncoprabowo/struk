self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('struk-cache').then(cache => {
      return cache.addAll(['/index.html']);
    })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
</script>
