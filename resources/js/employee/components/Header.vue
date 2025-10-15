<template>
  <header class="d-flex justify-content-between align-items-center mb-3 py-3">
    <div class="d-flex align-items-center gap-2">
      <img :src="logoUrl" alt="DOST Logo" height="28">
      <h5 class="fw-bold mb-0"> {{ title }} </h5>
    </div>
    
    <div class="d-flex gap-3 align-items-center">
      <!-- Notification Dropdown -->
      <div class="dropdown position-relative">
        <a 
          class="text-decoration-none position-relative d-inline-block" 
          href="#"
          id="notificationDropdown" 
          data-bs-toggle="dropdown" 
          aria-expanded="false"
          style="cursor: pointer;"
          @click="loadNotifications">
          <i class="fa-regular fa-bell text-dark" style="font-size: 1.5rem;"></i>
          <span 
            v-if="unreadCount > 0"
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
            style="font-size: 0.65rem; padding: 0.25rem 0.45rem;">
            {{ unreadCount }}
            <span class="visually-hidden">unread notifications</span>
          </span>
        </a>
        
        <ul 
          class="dropdown-menu dropdown-menu-end shadow-sm mt-2 p-0" 
          aria-labelledby="notificationDropdown" 
          style="min-width: 320px; max-width: 380px; border: 1px solid #e0e0e0;">
          
          <!-- Header -->
          <li class="px-3 py-2 border-bottom bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0 fw-semibold">Notifications</h6>
              <span v-if="unreadCount > 0" class="badge bg-danger rounded-pill">{{ unreadCount }}</span>
            </div>
          </li>
          
          <!-- Loading State -->
          <li v-if="loadingNotifications" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </li>
          
          <!-- Empty State -->
          <li v-else-if="notifications.length === 0" class="text-center py-4 text-muted">
            <i class="fa-regular fa-bell-slash mb-2" style="font-size: 2rem;"></i>
            <p class="mb-0">No notifications</p>
          </li>
          
          <!-- Notification Items -->
          <li v-else v-for="notification in notifications" :key="notification.id">
            <a 
              class="dropdown-item py-3 px-3 border-bottom" 
              href="#" 
              style="white-space: normal;"
              @click.prevent="markAsRead(notification.id)">
              <div class="d-flex gap-2">
                <div class="flex-shrink-0">
                  <div 
                    class="rounded-circle d-flex align-items-center justify-content-center" 
                    :class="getNotificationIconClass(notification.type)"
                    style="width: 40px; height: 40px;">
                    <i :class="getNotificationIcon(notification.type)"></i>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-1 fw-semibold text-dark" style="font-size: 0.9rem;">
                    {{ notification.title }}
                  </p>
                  <p class="mb-1 text-muted" style="font-size: 0.8rem;">
                    {{ notification.message }}
                  </p>
                  <small class="text-muted" style="font-size: 0.75rem;">
                    <i class="fa-regular fa-clock me-1"></i>{{ formatTime(notification.created_at) }}
                  </small>
                </div>
              </div>
            </a>
          </li>
          
          <!-- Footer -->
          <li class="border-top">
            <a 
              class="dropdown-item text-center py-2 text-primary fw-semibold" 
              href="#" 
              style="font-size: 0.9rem;"
              @click.prevent="viewAllNotifications">
              View all notifications
            </a>
          </li>
        </ul>
      </div>

      <!-- Profile Dropdown -->
      <div class="dropdown">
        <a 
          class="text-decoration-none position-relative d-inline-block" 
          href="#"
          id="profileDropdown" 
          data-bs-toggle="dropdown" 
          aria-expanded="false"
          style="cursor: pointer;">
          <img 
            :src="userAvatar" 
            alt="Profile" 
            class="rounded-circle" 
            style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #e0e0e0;">
          <span 
            class="position-absolute bg-primary rounded-circle d-flex align-items-center justify-content-center" 
            style="width: 18px; height: 18px; bottom: -2px; right: -2px; border: 2px solid white;">
            <i class="fa-solid fa-chevron-down text-white" style="font-size: 0.5rem;"></i>
          </span>
        </a>
        
        <ul 
          class="dropdown-menu dropdown-menu-end shadow-sm mt-2" 
          aria-labelledby="profileDropdown" 
          style="min-width: 220px; border: 1px solid #e0e0e0;">
          <li class="px-3 py-2 border-bottom">
            <div class="fw-semibold text-dark small">{{ user.name }}</div>
            <div class="text-muted" style="font-size: 0.75rem;">{{ user.email }}</div>
          </li>
          <li>
            <a class="dropdown-item py-2 px-3" href="/profile">
              <i class="fa-regular fa-user me-2" style="width: 18px;"></i>
              My Account
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 px-3" href="/settings">
              <i class="fa-solid fa-gear me-2" style="width: 18px;"></i>
              Settings
            </a>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <button 
              @click="logout" 
              class="dropdown-item py-2 px-3 text-danger w-100 text-start"
              :disabled="loggingOut">
              <i class="fa-solid fa-right-from-bracket me-2" style="width: 18px;"></i>
              {{ loggingOut ? 'Logging out...' : 'Logout' }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>

<script>
const token = localStorage.getItem('auth_token');
import axios from 'axios';

export default {
  name: 'AppHeader',
  props: {
    title: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      token: token,
      user: {
        name: 'User Name',
        email: 'user@example.com'
      },
      notifications: [],
      unreadCount: 0,
      loadingNotifications: false,
      loggingOut: false,
      logoUrl: '/img/dost.png'
    };
  },
  
  computed: {
    userAvatar() {
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(this.user.name)}&background=4f46e5&color=fff&size=128`;
    }
  },
  
  mounted() {
    this.fetchUser();
    this.fetchNotificationCount();
    // Poll for new notifications every 30 seconds
    this.notificationInterval = setInterval(() => {
      this.fetchNotificationCount();
    }, 30000);
  },
  
  beforeUnmount() {
    if (this.notificationInterval) {
      clearInterval(this.notificationInterval);
    }
  },
  
  methods: {
    async fetchUser() {
      try {
        const response = await axios.get('/api/user', {
          headers: {
            Authorization: `Bearer ${token}`
          }
        });
        this.user = response.data;
      } catch (error) {
        console.error('Error fetching user:', error);
      }
    },
    
    async fetchNotificationCount() {
      try {
        const response = await axios.get('/api/notifications/unread-count');
        this.unreadCount = response.data.count;
      } catch (error) {
        console.error('Error fetching notification count:', error);
      }
    },
    
    async loadNotifications() {
      if (this.notifications.length > 0) return; // Only load once
      
      this.loadingNotifications = true;
      try {
        const response = await axios.get('/api/notifications', {
          params: { limit: 5 }
        });
        this.notifications = response.data.data || response.data;
      } catch (error) {
        console.error('Error loading notifications:', error);
      } finally {
        this.loadingNotifications = false;
      }
    },
    
    async markAsRead(notificationId) {
      try {
        await axios.post(`/api/notifications/${notificationId}/read`);
        
        // Update local state
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && !notification.read_at) {
          notification.read_at = new Date();
          this.unreadCount = Math.max(0, this.unreadCount - 1);
        }
      } catch (error) {
        console.error('Error marking notification as read:', error);
      }
    },
    
    async logout() {
      if (this.loggingOut) return;
      
      this.loggingOut = true;
      try {
        await axios.post('/logout', {}, { withCredentials: true });
        window.location.href = '/login';
      } catch (error) {
        console.error('Error logging out:', error);
        alert('Failed to logout. Please try again.');
        this.loggingOut = false;
      }
    },
    
    viewAllNotifications() {
      window.location.href = '/notifications';
    },
    
    getNotificationIcon(type) {
      const icons = {
        user: 'fa-solid fa-user text-primary',
        success: 'fa-solid fa-check text-success',
        warning: 'fa-solid fa-exclamation text-warning',
        message: 'fa-solid fa-message text-info',
        alert: 'fa-solid fa-triangle-exclamation text-danger',
        default: 'fa-solid fa-bell text-primary'
      };
      return icons[type] || icons.default;
    },
    
    getNotificationIconClass(type) {
      const classes = {
        user: 'bg-primary bg-opacity-10',
        success: 'bg-success bg-opacity-10',
        warning: 'bg-warning bg-opacity-10',
        message: 'bg-info bg-opacity-10',
        alert: 'bg-danger bg-opacity-10',
        default: 'bg-primary bg-opacity-10'
      };
      return classes[type] || classes.default;
    },
    
    formatTime(timestamp) {
      const date = new Date(timestamp);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);
      
      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
      if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
      if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
      
      return date.toLocaleDateString();
    }
  }
};
</script>

<style lang="scss" scoped>
@import './../../../sass/variables';

.dropdown-item {
  &:hover {
    background-color: lighten($primary, 60);
    color: $dark;
    transition: background-color 0.2s ease;
  }
}

#notificationDropdown {
  &:hover {
    i {
      transform: rotate(15deg);
      transition: transform 0.3s ease;
    }
  }
}

#profileDropdown {
  &:hover {
    img {
      opacity: 0.9;
      transition: opacity 0.2s ease;
    }

    .bg-primary {
      transform: scale(1.1);
      transition: transform 0.2s ease;
    }
  }
}

.dropdown-menu {
  max-height: 500px;
  overflow-y: auto;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f1f1;
  }

  &::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;

    &:hover {
      background: #555;
    }
  }
}
</style>
