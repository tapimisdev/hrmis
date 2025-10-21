<template>
  <div class="profile-container">
    <div class="profile-header shadow">
      <div class="header-content">
        <div class="avatar-wrapper">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
          </div>
          <div class="status-indicator"></div>
        </div>
        <div class="user-info">
          <h1 class="user-name">Kim Mariano</h1>
          <p class="user-role">Practical Research IV</p>
          <p class="user-location">Calzada-Tipas Taguig City</p>
        </div>
        <div class="header-stats">
          <div class="stat-item">
            <span class="stat-value">127</span>
            <span class="stat-label">Projects</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">8.5K</span>
            <span class="stat-label">Followers</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">432</span>
            <span class="stat-label">Following</span>
          </div>
        </div>
      </div>
    </div>

    <div class="profile-body shadow">
      <div class="section-header">
        <h2>Update Basic Information</h2>
      </div>
      
      <form class="row g-3">
        <!-- Left Column -->
        <div class="col-md-6">
          <div class="mb-3">
            <label for="fullName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullName" value="Kim Mariano" placeholder="Enter your full name">
          </div>

          <div class="mb-3">
            <label for="role" class="form-label">Role/Position</label>
            <input type="text" class="form-control" id="role" value="Practical Research IV" placeholder="Enter your role">
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" value="Calzada-Tipas Taguig City" placeholder="Enter your location">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" value="kem.akoto@email.com" placeholder="Enter your email">
          </div>
        </div>

        <!-- Left Column -->
        <div class="col-md-6">
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control" id="phone" value="+1 (555) 123-4567" placeholder="Enter your phone number">
          </div>

          <div class="mb-3">
            <label for="division" class="form-label">Division</label>
            <input type="text" class="form-control" id="division" value="FAD" placeholder="Enter your division">
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <div class="mb-3">
            <label for="unit" class="form-label">Unit</label>
            <input type="text" class="form-control" id="unit" value="DOST TAPI" placeholder="Enter your unit">
          </div>

          <div class="mb-3">
            <label for="bio" class="form-label">Bio</label>
            <textarea id="bio" class="form-control" rows="3" placeholder="Tell us about yourself..."></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="col-12 text-end">
          <button type="button" class="btn btn-secondary me-2">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>


    </div>

    <div class="profile-details shadow">
      <div class="section-header">
        <h2>Details</h2>
      </div>
      
      <div class="detail-group">
        <div class="detail-item">
          <span class="detail-icon">📧</span>
          <div>
            <p class="detail-label">Email</p>
            <p class="detail-value">kem.akoto@email.com</p>
          </div>
        </div>
        
        <div class="detail-item">
          <span class="detail-icon">📞</span>
          <div>
            <p class="detail-label">Phone</p>
            <p class="detail-value">+1 (555) 123-4567</p>
          </div>
        </div>
        
        <div class="detail-item">
          <span class="detail-icon">🌐</span>
          <div>
            <p class="detail-label">Division</p>
            <p class="detail-value">FAD</p>
          </div>
        </div>
        
        <div class="detail-item">
          <span class="detail-icon">💼</span>
          <div>
            <p class="detail-label">Unit</p>
            <p class="detail-value">DOST TAPI</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">

@import './../../../sass/variables';

// ============================
// VARIABLES
// ============================
$gap: 1.25rem;
$margin-top: 80px;

$color-header: lighten($light, 2);
$color-body: #ffffff;
$color-details: #ffffff;
$color-accent: #ffffff;
$color-text: #1f2937;
$color-text-light: #6b7280;

$bp-tablet: 768px;
$bp-desktop: 1024px;

// ============================
// BASE STYLES
// ============================
.profile-container {
  display: grid;
  gap: $gap;
  grid-template-columns: 1fr;
  grid-template-rows: repeat(3, 1fr);
  margin-top: $margin-top;

  .profile-header,
  .profile-body,
  .profile-details {
    border-radius: 16px;
    padding: 2rem;
  }

  // ============================
  // HEADER STYLES
  // ============================
  .profile-header {
    grid-row: span 1;
    background: linear-gradient(135deg, $primary 0%, $secondary 100%);
    color: #fff;
    
    .header-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.5rem;
      height: 100%;
      justify-content: center;
    }

    .avatar-wrapper {
      position: relative;
      
      .avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 4px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        
        svg {
          width: 60%;
          height: 60%;
          opacity: 0.9;
        }
      }

      .status-indicator {
        position: absolute;
        bottom: 8px;
        right: 8px;
        width: 20px;
        height: 20px;
        background: #10b981;
        border-radius: 50%;
        border: 3px solid $primary;
      }
    }

    .user-info {
      text-align: center;
      
      .user-name {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        letter-spacing: -0.5px;
      }

      .user-role {
        font-size: 1.125rem;
        margin: 0 0 0.25rem 0;
        opacity: 0.95;
        font-weight: 500;
      }

      .user-location {
        font-size: 0.95rem;
        margin: 0;
        opacity: 0.8;
      }
    }

    .header-stats {
      display: flex;
      gap: 2.5rem;
      margin-top: 1rem;
      
      .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        
        .stat-value {
          font-size: 1.5rem;
          font-weight: 700;
          margin-bottom: 0.25rem;
        }

        .stat-label {
          font-size: 0.875rem;
          opacity: 0.85;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
      }
    }
  }

  // ============================
  // BODY STYLES
  // ============================
  .profile-body {
    grid-row: span 3;
    grid-row-start: 3;
    background-color: $color-body;
    color: $color-text;

    .section-header {
      margin-bottom: 1.5rem;
      
      h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: $color-text;
      }
    }


  }

  // ============================
  // DETAILS STYLES
  // ============================
  .profile-details {
    grid-row: span 1;
    grid-row-start: 2;
    background-color: $color-details;
    color: $color-text;

    .section-header {
      margin-bottom: 1.5rem;
      
      h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: $color-text;
      }
    }

    .detail-group {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
      margin-bottom: 2rem;
      
      .detail-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;

        .detail-icon {
          font-size: 1.5rem;
          flex-shrink: 0;
        }

        .detail-label {
          font-size: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          color: $color-text-light;
          margin: 0 0 0.25rem 0;
          font-weight: 600;
        }

        .detail-value {
          font-size: 0.95rem;
          color: $color-text;
          margin: 0;
          font-weight: 500;
        }
      }
    }

  }
}

// ============================
// TABLET (≥768px)
// ============================
@media (min-width: $bp-tablet) {
  .profile-container {
    margin-top: 24px;
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(7, 1fr);

    .profile-header {
      grid-column: span 3;
      grid-row: span 4;
    }

    .profile-body {
      grid-column: 1 / span 6;
      grid-row: 5 / span 3;
    }

    .profile-details {
      grid-column: 4 / span 3;
      grid-row: 1 / span 3;
    }
  }
}

// ============================
// DESKTOP (≥1024px)
// ============================
@media (min-width: $bp-desktop) {
  .profile-container {
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(9, 1fr);

    .profile-header {
      grid-column: 1 / span 3;
      grid-row: 1 / span 1;

      .header-content {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
      }

      .avatar-wrapper .avatar {
        width: 100px;
        height: 100px;
      }

      .user-info {
        text-align: left;
        flex: 1;
        margin-left: 2rem;
      }
    }

    .profile-body {
      grid-column: 1 / span 2;
      grid-row: 2 / span 3;
    }

    .profile-details {
      grid-column: 3 / span 1;
      grid-row: 2 / span 2;
    }
  }
}

// ============================
// SHADOW UTILITY
// ============================
.shadow {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
              0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>