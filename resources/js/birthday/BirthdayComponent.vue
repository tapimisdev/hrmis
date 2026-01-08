<template>
  <div class="wrapper" ref="wrapper" v-if="isVisible">
    <div class="image-box">
      <button class="close-btn" @click="close">×</button>

      <div class="text-group">
        <div class="birthday-text">🎉 Happy Birthday 🎉</div>
        <div class="birthday-name">{{ currentImage?.name }}</div>
      </div>

      <transition name="fade-scale" mode="out-in">
        <img
          v-if="currentImage"
          :key="currentIndex"
          :src="currentImage.profile"
          alt="Birthday Image"
        />
      </transition>
    </div>
  </div>
</template>

<script>
const token = localStorage.getItem('auth_token');

export default {
  data() {
    return {
      images: [],
      currentIndex: 0,
      intervalId: null,
      isVisible: false,
      isLoading: true,
    };
  },
  computed: {
    currentImage() {
      return this.images[this.currentIndex] || null;
    },
  },
  created() {
    // Check Laravel session to see if popup was already shown
    axios.get('/today-birthday', {
      headers: { Authorization: `Bearer ${token}` }
    }).then((response) => {
      // If response has data, show popup
      if (response.data.length > 0) {
        this.images = response.data;
        this.isVisible = true;
        this.startSlideshow();
        this.launchConfetti();
      }
    });

    // Ensure party.js is loaded
    if (!window.party) {
      const checkParty = setInterval(() => {
        if (window.party) clearInterval(checkParty);
      }, 50);
    }
  },
  beforeUnmount() {
    clearInterval(this.intervalId);
  },
  methods: {
    startSlideshow() {
      this.intervalId = setInterval(() => {
        this.nextImage();
        this.launchConfetti();
      }, 3000);
    },

    nextImage() {
      this.currentIndex = (this.currentIndex + 1) % this.images.length;
    },

    launchConfetti() {
      if (window.party && this.$refs.wrapper) {
        window.party.confetti(this.$refs.wrapper, {
          shapes: ["star", "roundedSquare"],
          count: window.party.variation.range(40, 200),
          size: window.party.variation.range(0.8, 1.4),
        });
      }
    },

    close() {
      clearInterval(this.intervalId);
      this.isVisible = false;
    },
  },
};
</script>

<style scoped lang="scss">
.wrapper {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(6px);
  z-index: 1082;
  display: flex;
  justify-content: center;
  align-items: center;
}

.image-box {
  position: relative;
  padding: 24px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  max-width: 90vw;
  animation: popIn 0.6s ease;

  img {
    width: 420px;
    height: 420px;
    object-fit: cover;
    border-radius: 14px;
  }
   @media (max-width: 520px) {
        img {
            width: 100%;
            height: auto;
        }
    }
}

.text-group {
  text-align: center;
  margin-bottom: 16px;
}

.birthday-text {
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, #ffd700, #ff8c00, #ff4d6d);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
}

.birthday-name {
  margin-top: 4px;
  font-size: 1.2rem;
  font-weight: 600;
  color: #fff;
  letter-spacing: 0.5px;
}

.close-btn {
  position: absolute;
  top: -26px;
  right: -26px;
  background: rgba($color: #fff, $alpha: .2);
  border: none;
  border-radius: 50%;
  width: 26px;
  height: 26px;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
  transition: transform 0.2s ease, background 0.2s ease;

  &:hover {
    transform: scale(1.1) rotate(90deg);
    background: #ffd700;
  }
}

/* Animations */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.5s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

@keyframes popIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
