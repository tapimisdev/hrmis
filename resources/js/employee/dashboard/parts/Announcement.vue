<template>
  <div class="announcements-section">
    <div class="section-header">
      <div>
        <h5 class="section-title">Announcements</h5>
        <p class="section-subtitle">Stay updated with the latest company news</p>
      </div>
      <a href="/announcements" class="view-all-link">
        View All <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <div class="announcements-grid">
      <article
        v-for="announcement in announcements"
        :key="announcement.id"
        class="announcement-card">
        <a :href="announcement.url" target="_blank" rel="noopener noreferrer" class="card-link">
          <div class="card-image">
            <img
              :src="announcement.image"
              :alt="announcement.name"
              loading="lazy"
            />
            <div class="card-overlay">
              <span class="read-badge">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Read More
              </span>
            </div>
          </div>
          
          <div class="card-content">
            <div class="card-tags">
              <span v-for="tag in announcement.tags.slice(0, 2)" :key="tag" class="tag">
                {{ tag }}
              </span>
            </div>
            
            <h6 class="card-title">{{ announcement.name }}</h6>
            <p class="card-description" v-html="announcement.body"></p>
            
            <div class="card-footer">
              <div class="seeners">
                <div class="seeners-avatars">
                  <img
                    v-for="(seener, idx) in announcement.seeners.slice(0, 3)"
                    :key="seener.id"
                    :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(seener.name)}&background=random&size=64`"
                    :alt="seener.name"
                    :title="seener.name"
                    class="seener-avatar"
                    :style="{ zIndex: 3 - idx }"
                  />
                </div>
                <span class="seeners-count">
                  {{ announcement.seeners.length }}+ viewed
                </span>
              </div>
            </div>
          </div>
        </a>
      </article>
    </div>
  </div>
</template>

<script>
export default {
  name: "Announcement",
  data() {
    return {
      announcements: [],
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
        console.error('Error fetching stats:', error);
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

.announcement-card {
  background: var(--bs-secondary-bg);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
    
    .card-image img {
      transform: scale(1.05);
    }
    
    .card-overlay {
      opacity: 1;
    }
  }
}

.card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.card-image {
  position: relative;
  height: 180px;
  overflow: hidden;
  background: linear-gradient(135deg, var(--bs-primary-rgb) 0%, $secondary 100%);
  
  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }
  
  .card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .read-badge {
    background: white;
    color: var(--bs-primary);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    
    i {
      font-size: 0.75rem;
    }
  }
}

.card-content {
  padding: 1.25rem;
}

.card-tags {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

[data-bs-theme="dark"] {
  .tag {
    background: lighten($primary, 30%);
  }
}

.tag {
  background: $primary;
  color: var(--bs-light);
  padding: 0.25rem 0.65rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: capitalize;
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--bg-body-color);
  margin: 0 0 0.75rem 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-description {
  font-size: 0.8rem;
  color: #6c757d;
  margin: 0 0 1rem 0;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  padding-top: 1rem;
}

.seeners {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.seeners-avatars {
  display: flex;
  align-items: center;
}

.seener-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid white;
  object-fit: cover;
  
  &:not(:first-child) {
    margin-left: -10px;
  }
}

.seeners-count {
  font-size: 0.75rem;
  color: #6c757d;
  font-weight: 600;
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
  
  .card-image {
    height: 160px;
  }
  
  .card-content {
    padding: 1rem;
  }
}

@media (max-width: 576px) {
  .announcements-grid {
    grid-template-columns: 1fr;
  }
  
  .card-image {
    height: 200px;
  }
}
</style>