<template>
  <div class="container-fluid profile-container">
    <!-- HEADER WITH COVER -->
    <div class="profile-cover">
      <div class="cover-gradient"></div>
      <div class="profile-main">
        <div class="avatar-wrapper">
          <div class="avatar">
            <img
              v-if="personal.profile"
              :src="personal.profile"
              alt="Profile"
            />
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
          </div>
          <div class="status-badge">Active</div>
        </div>
        
        <div class="profile-identity">
          <div class="name-section">
            <h1>{{ fullName }}</h1>
            <span class="employee-id">{{ personal.employee_no }}</span>
          </div>
          <div class="role-badge">{{ organization.position_name }}</div>
          <div class="meta-info">
            <div class="meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              <span>{{ organization.employment_type_name }}</span>
            </div>
            <div class="meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              <span>Joined {{ organization.effectivity_date }}</span>
            </div>
            <div class="meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              </svg>
              <span>{{ organization.unit_name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CONTENT SECTIONS -->
    <div class="content-wrapper">
      <div class="content-grid">
        
        <!-- Personal Information -->
        <section class="info-card border">
          <div class="card-header">
            <h2>Personal Information</h2>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <DetailItem label="Biometrics ID" :value="personal.biometrics_id" />
              <DetailItem icon="calendar" label="Birthday" :value="personal.birthday" />
              <DetailItem icon="hash" label="Age" :value="personal.age" />
              <DetailItem icon="user" label="Sex" :value="personal.sex" />
              <DetailItem icon="heart" label="Civil Status" :value="personal.civil_status" />
              <DetailItem icon="flag" label="Citizenship" :value="personal.citizenship" />
              <DetailItem icon="globe" label="Country" :value="personal.country" />
              <DetailItem icon="map-pin" label="Birth Place" :value="personal.birth_place" />
              <DetailItem icon="activity" label="Height" :value="personal.height" />
              <DetailItem icon="activity" label="Weight" :value="personal.weight" />
              <DetailItem icon="droplet" label="Blood Type" :value="personal.blood_type" />
            </div>
          </div>
        </section>

        <!-- Government IDs -->
        <section class="info-card border">
          <div class="card-header">
            <h2>Government IDs</h2>
          </div>
          <div class="card-body">
            <div class="id-list">
              <div class="id-item">
                <span class="id-label">GSIS</span>
                <span class="id-value">{{ personal.gsis_no || '—' }}</span>
              </div>
              <div class="id-item">
                <span class="id-label">PAG-IBIG</span>
                <span class="id-value">{{ personal.pagibig_no || '—' }}</span>
              </div>
              <div class="id-item">
                <span class="id-label">PhilHealth</span>
                <span class="id-value">{{ personal.philhealth_no || '—' }}</span>
              </div>
              <div class="id-item">
                <span class="id-label">SSS</span>
                <span class="id-value">{{ personal.sss_no || '—' }}</span>
              </div>
              <div class="id-item">
                <span class="id-label">TIN</span>
                <span class="id-value">{{ personal.tin_no || '—' }}</span>
              </div>
              <div class="id-item">
                <span class="id-label">PhilSys</span>
                <span class="id-value">{{ personal.philsys_no || '—' }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Address Information -->
        <section class="info-card border full-width">
          <div class="card-header">
            <h2>Address Information</h2>
          </div>
          <div class="card-body">
            <div class="address-sections">
              <div class="address-block">
                <h3>Present Address</h3>
                <div class="address-grid">
                  <DetailItem label="Block" :value="personal.present_block" />
                  <DetailItem label="Street" :value="personal.present_street" />
                  <DetailItem label="Subdivision" :value="personal.present_subdivision" />
                  <DetailItem label="Barangay" :value="personal.present_barangay" />
                  <DetailItem label="City" :value="personal.present_city" />
                  <DetailItem label="Province" :value="personal.present_province" />
                  <DetailItem label="ZIP Code" :value="personal.present_zip" />
                </div>
              </div>
              
              <div class="address-block">
                <h3>Permanent Address</h3>
                <div class="address-grid">
                  <DetailItem label="Block" :value="personal.permanent_block" />
                  <DetailItem label="Street" :value="personal.permanent_street" />
                  <DetailItem label="Subdivision" :value="personal.permanent_subdivision" />
                  <DetailItem label="Barangay" :value="personal.permanent_barangay" />
                  <DetailItem label="City" :value="personal.permanent_city" />
                  <DetailItem label="Province" :value="personal.permanent_province" />
                  <DetailItem label="ZIP Code" :value="personal.permanent_zip" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <slot />

        <!-- Organization Details -->
        <section class="info-card border">
          <div class="card-header">
            <h2>Organization Details</h2>
          </div>
          <div class="card-body">
            <div class="org-grid">
              <DetailItem label="Position" :value="organization.position_name" />
              <DetailItem label="Unit" :value="organization.unit_name" />
              <DetailItem label="Division" :value="organization.division_name" />
              <DetailItem label="Employment Type" :value="organization.employment_type_name" />
            </div>
          </div>
        </section>

      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

// Simple DetailItem component inline
const DetailItem = {
  props: {
    label: String,
    value: [String, Number],
    icon: String
  },
  template: `
    <div class="detail-item">
      <span class="detail-label">{{ label }}</span>
      <span class="detail-value">{{ value || '—' }}</span>
    </div>
  `
};

export default {
  components: { DetailItem },
  data() {
    return {
      personal: {},
      organization: {},
      user: {},
    };
  },

  computed: {
    fullName() {
      return [
        this.personal.firstname,
        this.personal.middlename,
        this.personal.lastname,
        this.personal.suffix,
      ]
        .filter(Boolean)
        .join(" ");
    },
  },

  mounted() {
    this.fetchProfile();
  },

  methods: {
    fetchProfile() {
      axios.get("/employee/profile", { headers: { Accept: "application/json" } })
        .then(({ data }) => {
          this.user = data.user || {};
          this.personal = data.personal || {};
          this.organization = data.organization || {};
        })
        .catch(err => console.error(err));
    },
  },
};
</script>

<style scoped lang="scss">
@import './../../../../sass/variables';

.profile-container {
  min-height: 100vh;
  padding: 0;
}

.profile-cover {
  position: relative;
  height: 280px;
  background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-secondary) 100%);
  overflow: hidden;
  margin-bottom: 26px;
  border-radius: 16px;
  
  .cover-gradient {
    position: absolute;
    inset: 0;
    background: 
      radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
  }
  
  .profile-main {
    position: relative;
    max-width: 100%;
    padding: 2rem;
    display: flex;
    align-items: flex-end;
    gap: 2rem;
    height: 100%;
    padding-bottom: 1rem;
  }
}

.avatar-wrapper {
  position: relative;
  flex-shrink: 0;
  
  .avatar {
    width: 140px;
    height: 140px;
    border-radius: 20px;
    background: white;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    border: 4px solid white;
    
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    svg {
      width: 100%;
      height: 100%;
      color: $light;
    }
  }
  
  .status-badge {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    background: $success;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }
}

.profile-identity {
  flex: 1;
  padding-bottom: 1rem;
  
  .name-section {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 0.5rem;
    
    h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: 700;
      color: white;
      letter-spacing: -0.5px;
    }
    
    .employee-id {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.875rem;
      font-weight: 500;
    }
  }
  
  .role-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }
  
  .meta-info {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    
    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: rgba(255, 255, 255, 0.95);
      font-size: 0.875rem;
      
      svg {
        opacity: 0.8;
      }
    }
  }
}

.content-wrapper {
  background-color: var(--bs-body-bg);
  max-width: 100%;
}

.content-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: 1fr;
  
  @media (min-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .full-width {
    grid-column: 1 / -1;
  }
}

.info-card {
  background: var(--bs-secondary-bg);
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: box-shadow 0.3s ease;
  
  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
  
  .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
    h2 {
      margin: 0;
      font-size: 1.125rem;
      font-weight: 700;
      color: var(--bs-body-color);
    }
  }
  
  .card-body {
    padding: 1.5rem;
  }
}

.info-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  
  .detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: lighten($dark, 30%);
  }
  
  .detail-value {
    font-size: 0.9375rem;
    color: $dark;
    font-weight: 500;
  }
}

.id-list {
  display: grid;
  gap: 1rem;
  
  .id-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .875rem;
    background: var(--bs-body-secondary);
    border-radius: 12px;
    border: 1px solid var(--bs-border-color-translucent);
    transition: all 0.2s ease;
    
    .id-label {
      font-weight: 400;
      color: var(--bs-body-color);
      font-size: 0.875rem;
    }
    
    .id-value {
      color: lighten($dark, 20%);
      font-size: 0.875rem;
      font-family: 'Monaco', 'Courier New', monospace;
    }
  }
}

.address-sections {
  display: grid;
  gap: 2rem;
  
  @media (min-width: 1024px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .address-block {
    h3 {
      margin: 0 0 1rem;
      font-size: 1rem;
      font-weight: 600;
      color: var(--bs-primary);
    }

    .address-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
  }
}

.org-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

@media (max-width: 767px) {
  .profile-cover {
    height: auto;
    
    .profile-main {
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding-top: 2rem;
      padding-bottom: 2rem;
    }
  }
  
  .avatar-wrapper .avatar {
    width: 120px;
    height: 120px;
  }
  
  .profile-identity .name-section {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .profile-identity .meta-info {
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
  }
  
  .content-wrapper {
    transform: none;
    padding: 1rem;
  }
}
</style>