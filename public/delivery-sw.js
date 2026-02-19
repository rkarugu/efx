const CACHE_NAME = 'delivery-app-v1';
const urlsToCache = [
  '/admin/delivery-driver/dashboard',
  '/admin/delivery-driver/history',
  '/assets/admin/bower_components/bootstrap/dist/css/bootstrap.min.css',
  '/assets/fontawesome/css/fontawesome.css',
  '/assets/fontawesome/css/solid.css',
  '/assets/admin/bower_components/jquery/dist/jquery.min.js',
  '/assets/images/delivery-icon-192.png',
  '/assets/images/delivery-icon-512.png'
];

// Install Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Return cached version or fetch from network
        if (response) {
          return response;
        }
        
        return fetch(event.request).then((response) => {
          // Check if we received a valid response
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone the response
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });

          return response;
        }).catch(() => {
          // Return offline page for navigation requests
          if (event.request.mode === 'navigate') {
            return caches.match('/admin/delivery-driver/dashboard');
          }
        });
      })
  );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background Sync
self.addEventListener('sync', (event) => {
  if (event.tag === 'delivery-sync') {
    event.waitUntil(syncDeliveryData());
  }
});

// Push Notifications
self.addEventListener('push', (event) => {
  const options = {
    body: event.data ? event.data.text() : 'New delivery notification',
    icon: '/assets/images/delivery-icon-192.png',
    badge: '/assets/images/delivery-icon-96.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/assets/images/checkmark.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/assets/images/xmark.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Delivery App', options)
  );
});

// Notification Click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/admin/delivery-driver/dashboard')
    );
  }
});

// Sync delivery data when online
async function syncDeliveryData() {
  try {
    // Get pending actions from IndexedDB
    const pendingActions = await getPendingActions();
    
    for (const action of pendingActions) {
      try {
        await fetch(action.url, {
          method: action.method,
          headers: action.headers,
          body: action.body
        });
        
        // Remove from pending actions
        await removePendingAction(action.id);
      } catch (error) {
        console.log('Failed to sync action:', error);
      }
    }
  } catch (error) {
    console.log('Sync failed:', error);
  }
}

// IndexedDB helpers (simplified)
async function getPendingActions() {
  // Implementation would use IndexedDB to store pending actions
  return [];
}

async function removePendingAction(id) {
  // Implementation would remove action from IndexedDB
}
