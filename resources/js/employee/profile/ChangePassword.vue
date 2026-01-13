<template>
  <div class="card">
    <!-- Card Body -->
    <div class="card-body">
      <form @submit.prevent="submitChangePassword">
        <!-- Current Password -->
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input
            type="password"
            class="form-control"
            v-model="password.current"
            :class="{ 'is-invalid': errors.current_password }"
          />
          <div v-if="errors.current_password" class="invalid-feedback">
            {{ errors.current_password[0] }}
          </div>
        </div>

        <!-- New Password -->
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input
            type="password"
            class="form-control"
            v-model="password.new"
            :class="{ 'is-invalid': errors.new_password }"
          />
          <div v-if="errors.new_password" class="invalid-feedback">
            {{ errors.new_password[0] }}
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
          <label class="form-label">Confirm New Password</label>
          <input
            type="password"
            class="form-control"
            v-model="password.confirm"
            :class="{ 'is-invalid': errors.new_password_confirmation }"
          />
          <div v-if="errors.new_password_confirmation" class="invalid-feedback">
            {{ errors.new_password_confirmation[0] }}
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end">
          <button
            type="submit"
            class="btn btn-primary px-4"
            :disabled="loading"
          >
            <span
              v-if="loading"
              class="spinner-border spinner-border-sm me-2"
            ></span>
            {{ loading ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
const token = localStorage.getItem('auth_token');

export default {
    data() {
        return {
            password: {
                current: '',
                new: '',
                confirm: ''
            },
            errors: {},
            loading: false
        };
    },
    methods: {
        async submitChangePassword() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await axios.put(
                    '/employee/change-password',
                    {
                        current_password: this.password.current,
                        new_password: this.password.new,
                        new_password_confirmation: this.password.confirm
                    },
                    {
                        headers: {
                            Authorization: `Bearer ${this.token}`,
                            Accept: 'application/json'
                        }
                    }
                );

                SuccesToast.fire({
                    title: response.data.message || 'Password changed successfully'
                });

                // clear fields
                this.password.current = '';
                this.password.new = '';
                this.password.confirm = '';

            } catch (error) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors;
                } else {
                    ErrorToast.fire({
                        title:
                            error.response?.data?.message ||
                            'An error occurred'
                    });
                }
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
<style scoped lang="scss">
@import './../../../sass/variables';

.is-invalid {
    border-color: $danger !important;
}

</style>