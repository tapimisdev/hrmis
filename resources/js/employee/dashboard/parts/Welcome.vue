<template>
  <div class="welcome-header">
    <div class="row align-items-center g-3">
      <div class="col-md-5 order-md-2">
        <div class="image-container">
          <img src="../../img/engineering.svg" alt="Engineering" class="illustration" />
        </div>
      </div>
      <div class="col-md-7 order-md-1">
        <div class="welcome-content">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="greeting mb-1">
                <span class="wave">👋</span>
                <span class="greeting-text">Good {{ timeOfDay }},</span>
              </div>
              <h4 class="user-name mb-0">{{ userName }}</h4>
            </div>
            <div class="status-badge" :class="timeStatus.class">
              <i :class="timeStatus.icon"></i>
              <span>{{ timeStatus.text }}</span>
            </div>
          </div>
          
          <p class="welcome-message mb-3">
            {{ timeStatus.message }}
          </p>
          
          <div class="action-buttons d-flex gap-2 mb-3">
            <button 
              v-if="!isTimedIn"
              @click="timeIn"
              class="btn btn-warning px-4 py-2 d-flex align-items-center gap-2"
              :disabled="loading">
              <i class="fa-solid fa-clock"></i>
              <span>Record Your Attendance</span>
            </button>
            <button class="btn btn-outline-light px-4 py-2 d-flex align-items-center gap-2">
              <i class="fa-regular fa-calendar"></i>
              <span>View Schedule</span>
            </button>
          </div>
          
          <div class="quick-stats d-flex gap-3">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
              </div>
              <div>
                <div class="stat-value">{{ stats.timeInToday }}</div>
                <div class="stat-label">Time In Today</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fa-solid fa-business-time"></i>
              </div>
              <div>
                <div class="stat-value">{{ stats.hoursToday }}</div>
                <div class="stat-label">Hours Today</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fa-solid fa-calendar-check"></i>
              </div>
              <div>
                <div class="stat-value">{{ stats.daysPresent }}</div>
                <div class="stat-label">Days Present</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fa-solid fa-umbrella-beach"></i>
              </div>
              <div>
                <div class="stat-value">{{ stats.leavesLeft }}</div>
                <div class="stat-label">Leaves Left</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: "WelcomeHeader",
  data() {
    return {
      userName: "Kemuel Mariano",
      isTimedIn: false,
      loading: false,
      timeInAt: null,
      stats: {
        hoursToday: "0h",
        timeInToday: "07:12 AM",
        daysPresent: 20,
        leavesLeft: 10
      }
    };
  },
  computed: {
    timeOfDay() {
      const hour = new Date().getHours();
      if (hour < 12) return 'morning';
      if (hour < 18) return 'afternoon';
      return 'evening';
    },
    timeStatus() {
      if (this.isTimedIn) {
        return {
          text: 'Timed In',
          class: 'status-active',
          icon: 'fa-solid fa-circle-check',
          message: `You're clocked in since ${this.timeInAt}. Have a productive day!`
        };
      }
      return {
        text: 'Not Timed In',
        class: 'status-inactive',
        icon: 'fa-solid fa-circle-xmark',
        message: "Don't forget to clock in to start tracking your time."
      };
    }
  },
  mounted() {
    this.fetchTimeStatus();
    this.fetchStats();
  },
  methods: {
    async fetchTimeStatus() {
      try {
        const response = await axios.get('/api/attendance/status');
        this.isTimedIn = response.data.is_timed_in;
        this.timeInAt = response.data.time_in;
        if (this.isTimedIn && response.data.hours_today) {
          this.stats.hoursToday = response.data.hours_today;
        }
      } catch (error) {
        console.error('Error fetching time status:', error);
      }
    },
    async fetchStats() {
      try {
        const response = await axios.get('/api/attendance/stats');
        this.stats = {
          hoursToday: response.data.hours_today || '0h',
          daysPresent: response.data.days_present || 0,
          leavesLeft: response.data.leaves_left || 0
        };
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    },
    async timeIn() {
      this.loading = true;
      try {
        const response = await axios.post('/api/attendance/time-in');
        this.isTimedIn = true;
        this.timeInAt = response.data.time_in;
        this.$emit('time-status-changed', { action: 'time-in', data: response.data });
      } catch (error) {
        console.error('Error timing in:', error);
        alert(error.response?.data?.message || 'Failed to time in');
      } finally {
        this.loading = false;
      }
    },
    async timeOut() {
      this.loading = true;
      try {
        const response = await axios.post('/api/attendance/time-out');
        this.isTimedIn = false;
        this.timeInAt = null;
        this.stats.hoursToday = response.data.hours_today;
        this.$emit('time-status-changed', { action: 'time-out', data: response.data });
      } catch (error) {
        console.error('Error timing out:', error);
        alert(error.response?.data?.message || 'Failed to time out');
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.welcome-header {
  background: linear-gradient(135deg, $secondary 0%, $primary 100%);
  border-radius: 10px;
  padding: 1.75rem 1.5rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
  
  &::before {
    content: '';
    position: absolute;
    top: -30%;
    right: -15%;
    width: 700px;
    height: 700px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 50%;
    z-index: 0;
  }
  
  .row {
    position: relative;
    z-index: 1;
  }
}

.image-container {
  display: flex;
  justify-content: center;
  align-items: center;
  
  .illustration {
    max-width: 100%;
    height: auto;
    max-height: 240px;
    animation: float 3s ease-in-out infinite reverse;
  }
}

.welcome-content {
  color: $light;
  
  .greeting {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    
    .wave {
      font-size: 1.2rem;
    }
    
    .greeting-text {
      font-size: 0.95rem;
      font-weight: 500;
      opacity: 0.9;
    }
  }
  
  .user-name {
    font-size: 1.75rem;
    font-weight: 700;
    background: linear-gradient(to right, #ffffff, #f5f5f5);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .status-badge {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    
    &.status-active {
      background: rgba(40, 167, 69, 0.9);
      color: white;
    }
    
    &.status-inactive {
      background: rgba(220, 53, 69, 0.9);
      color: white;
    }
    
    i {
      font-size: 0.9rem;
    }
  }
  
  .welcome-message {
    font-size: 0.9rem;
    line-height: 1.5;
    opacity: 0.9;
  }
  
  .action-buttons {
    .btn {
      font-weight: 600;
      border-radius: 10px;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      
      &.btn-warning {
        border: none;
        
        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(255, 193, 7, 0.4);
        }
      }
      
      &.btn-danger {
        border: none;
        
        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
        }
      }
      
      &.btn-outline-light {
        border-color: rgba(255, 255, 255, 0.6);
        color: white !important;
        background: rgba(255, 255, 255, 0.1);
        
        &:hover {
          background: rgba(255, 255, 255, 0.2);
          border-color: white;
          color: $primary !important;
          transform: translateY(-2px);
        }
      }
      
      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
    }
  }
  
  .quick-stats {
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      
      .stat-icon {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        
        i {
          font-size: 1rem;
        }
      }
      
      .stat-value {
        font-size: 1.4rem;
        font-weight: 700;
        line-height: 1;
      }
      
      .stat-label {
        font-size: 0.75rem;
        opacity: 0.8;
        line-height: 1.2;
      }
    }
  }
}

// Responsive styles
@media (max-width: 768px) {
  .welcome-header {
    padding: 1.5rem 1.25rem;
  }
  
  .welcome-content {
    .status-badge {
      font-size: 0.8rem;
      padding: 0.35rem 0.75rem;
    }
    
    .user-name {
      font-size: 1.5rem;
    }
    
    .action-buttons {
      .btn {
        flex: 1;
        min-width: 120px;
        font-size: 0.85rem;
      }
    }
    
    .quick-stats {
      flex-wrap: wrap;
      
      .stat-item {
        .stat-icon {
          width: 32px;
          height: 32px;
        }
        
        .stat-value {
          font-size: 1.2rem;
        }
      }
    }
  }
  
  .image-container {
    .illustration {
      max-height: 180px;
    }
  }
}

@media (max-width: 576px) {
  .welcome-content {
    .d-flex.justify-content-between {
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .action-buttons {
      flex-direction: column;
      
      .btn {
        width: 100%;
      }
    }
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) translateX(0%);
  }
  50% {
    transform: translateY(-10px) translateX(0%);
  }
}
</style>