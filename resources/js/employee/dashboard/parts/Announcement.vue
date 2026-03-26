<template>
  <div class="announcements-section">
    <div class="section-header">
      <div>
        <h5 class="section-title">Announcements</h5>
        <p class="section-subtitle">Stay updated with the latest company news</p>
      </div>
      <a href="/employee/announcements" class="view-all-link">
        View All <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <div class="announcements-grid">
        <AnnouncementCard v-for="announcement in announcements" :key="announcement.id" :announcement="announcement"/>
    </div>
  </div>

</template>

<script>
const token = localStorage.getItem("auth_token");
import AnnouncementCard from '../../announcements/AnnouncementCard.vue';

export default {
  name: "Announcement",
  components: { AnnouncementCard },
  data() {
    return {
      announcements: [],
      token: token
    };
  },
  mounted() {
    this.fetchAnnouncements();
  },
  methods: {
    async fetchAnnouncements() {
      try {
        const response = await axios.get('/employee/get-announcements', {
          headers: { Authorization: `Bearer ${this.token}` },
        });

        this.announcements = response.data.data;
      } catch (error) {
      }
    },
  }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.announcements-section {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding: 0 0.25rem;

  .section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0 0 0.25rem 0;
  }

  .section-subtitle {
    font-size: 0.85rem;
    color: var(--bs-tertiary-color);
    margin: 0;
  }
  
  .view-all-link {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.2s ease;
    
    &:hover {
      gap: 0.5rem;
      color: var(--bs-primary);
    }
    
    i {
      font-size: 0.75rem;
    }
  }
}

.announcements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

// Responsive
@media (max-width: 1200px) {
  .announcements-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  }
}

@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .announcements-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 576px) {
  .announcements-grid {
    grid-template-columns: 1fr;
  }
}
</style>