<template>
  <div class="profile-container">
    <!-- Header Section -->
    <div class="profile-header">
      <div class="avatar-section">
        <div class="avatar">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
          </svg>
        </div>
        <div class="user-info">
          <h1>Kim Mariano</h1>
          <p class="role">Research Specialist IV</p>
          <p class="id">Employee ID: EMP-2024-1547</p>
        </div>
      </div>
      <div class="employment-info">
        <div class="info-item">
          <span class="label">Department</span>
          <strong>Research & Development</strong>
        </div>
        <div class="info-item">
          <span class="label">Employment Status</span>
          <strong>Regular</strong>
        </div>
        <div class="info-item">
          <span class="label">Date Hired</span>
          <strong>Jan 15, 2020</strong>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content-grid">
      <!-- Update Form -->
      <div class="card form-card">
        <h2>Update Employee Information</h2>
        <form>
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" value="Kim Mariano" placeholder="Enter full name">
            </div>
            <div class="form-group">
              <label>Employee ID</label>
              <input type="text" value="EMP-2024-1547" placeholder="Employee ID" disabled>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Position</label>
              <input type="text" value="Research Specialist IV" placeholder="Enter position">
            </div>
            <div class="form-group">
              <label>Department</label>
              <input type="text" value="Research & Development" placeholder="Enter department">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Division</label>
              <input type="text" value="FAD" placeholder="Enter division">
            </div>
            <div class="form-group">
              <label>Unit/Section</label>
              <input type="text" value="DOST TAPI" placeholder="Enter unit">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Work Location</label>
              <input type="text" value="Calzada-Tipas Taguig City" placeholder="Enter work location">
            </div>
            <div class="form-group">
              <label>Employment Status</label>
              <select>
                <option value="regular" selected>Regular</option>
                <option value="probationary">Probationary</option>
                <option value="contractual">Contractual</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" value="kim.mariano@company.com" placeholder="Enter email">
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="tel" value="+63 917 123 4567" placeholder="Enter contact number">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>

      <!-- Details Card -->
      <div class="card details-card">
        <h2>Employee Details</h2>
        <div class="detail-list">
          <div class="detail">
            <span class="icon">👤</span>
            <div>
              <label>Employee ID</label>
              <p>EMP-2024-1547</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">📧</span>
            <div>
              <label>Email</label>
              <p>kim.mariano@company.com</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">📞</span>
            <div>
              <label>Contact</label>
              <p>+63 917 123 4567</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">🏢</span>
            <div>
              <label>Department</label>
              <p>Research & Development</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">🌐</span>
            <div>
              <label>Division</label>
              <p>FAD</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">💼</span>
            <div>
              <label>Unit</label>
              <p>DOST TAPI</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">📍</span>
            <div>
              <label>Work Location</label>
              <p>Calzada-Tipas Taguig City</p>
            </div>
          </div>
          <div class="detail">
            <span class="icon">📅</span>
            <div>
              <label>Date Hired</label>
              <p>January 15, 2020</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Change Password Card -->
      <div class="card password-card">
        <h2>Change Password</h2>
        <form>
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" placeholder="Enter current password">
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" placeholder="Enter new password">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" placeholder="Confirm new password">
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
@import './../../../sass/variables';

.profile-header {
  background: linear-gradient(135deg, $primary 0%, $secondary 100%);
  border-radius: 12px;
  padding: 2rem;
  color: white;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;

  .avatar-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    
    .avatar {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      
      svg {
        width: 50%;
        height: 50%;
      }
    }
    
    .user-info {
      h1 {
        margin: 0 0 0.25rem;
        font-size: 1.5rem;
        font-weight: 600;
      }
      
      .role {
        margin: 0 0 0.25rem;
        opacity: 0.95;
        font-size: 0.95rem;
      }
      
      .id {
        margin: 0;
        opacity: 0.8;
        font-size: 0.875rem;
      }
    }
  }
  
  .employment-info {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    
    .info-item {
      display: flex;
      flex-direction: column;
      
      .label {
        font-size: 0.75rem;
        opacity: 0.85;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
      }
      
      strong {
        font-size: 0.95rem;
        font-weight: 600;
      }
    }
  }
}

.content-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: 1fr;
}

.card {
  background: var(--bs-secondary-bg);
  border-radius: 12px;
  padding: 1.5rem;
  
  h2 {
    margin: 0 0 1.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--bs-body-color);
  }
}

.form-card {
  form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-row {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    
    label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--bs-body-color);
    }
    
    input, select, textarea {
      padding: 0.625rem;
      border: 1px solid var(--bs-border-color);
      border-radius: 6px;
      font-size: 0.875rem;
      background: var(--bs-body-bg);
      color: var(--bs-body-color);
      
      &:focus {
        outline: none;
        border-color: $primary;
      }
      
      &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
    }
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 0.5rem;
  }
}

.details-card {
  .detail-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }
  
  .detail {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    
    .icon {
      font-size: 1.25rem;
    }
    
    label {
      display: block;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #6b7280;
      margin-bottom: 0.25rem;
      font-weight: 600;
    }
    
    p {
      margin: 0;
      font-size: 0.875rem;
      color: var(--bs-body-color);
    }
  }
}

.password-card {
  form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    
    label {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--bs-body-color);
    }
    
    input {
      padding: 0.625rem;
      border: 1px solid var(--bs-border-color);
      border-radius: 6px;
      font-size: 0.875rem;
      background: var(--bs-body-bg);
      color: var(--bs-body-color);
      
      &:focus {
        outline: none;
        border-color: $primary;
      }
    }
  }
  
  .form-actions {
    margin-top: 0.5rem;
    
    button {
      padding: 0.625rem 1.25rem;
      border-radius: 6px;
      font-size: 0.875rem;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      
      &.full {
        width: 100%;
      }
    }
    
    .btn-primary {
      background: $primary;
      color: white;
      
      &:hover {
        opacity: 0.9;
      }
    }
  }
}

@media (min-width: 768px) {
  .profile-header {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  
  .content-grid {
    grid-template-columns: 2fr 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr 1fr !important;
  }
}

@media (min-width: 1024px) {
  .profile-header .avatar-section .avatar {
    width: 90px;
    height: 90px;
  }
}
</style>