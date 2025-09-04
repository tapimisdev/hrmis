<template>
  <div class="view-items">
    <div 
      class="view-item text-center p-2"
      v-for="(profile, index) in profiles" 
      :key="index"
    >
      <img 
        class="rounded-circle img-fluid"
        :src="profile.image" 
        alt="profile"
      >
      <!-- Status indicator -->
      <div 
        class="status-indicator"
        :class="{
          'bg-success': profile.status === 'time-in',
          'bg-danger': profile.status === 'not-yet',
          'bg-warning': profile.status === 'break'
        }"
      ></div>

      <div class="mt-2 fw-bold name">{{ profile.name }}</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ViewLogs",
  data() {
    return {
      profiles: [
        {
          name: "John Doe",
          image: "https://img.freepik.com/premium-photo/stylish-man-flat-vector-profile-picture-ai-generated_606187-314.jpg",
          status: "time-in", // ✅ green
        },
        {
          name: "Kim Mariano",
          image: "https://tse4.explicit.bing.net/th/id/OIP.JUFUP-y7lki4FGAXlmXK_QHaHa?pid=Api&P=0&h=180",
          status: "not-yet", // ✅ red
        },
        {
          name: "Mark Lee",
          image: "https://fiverr-res.cloudinary.com/images/q_auto,f_auto/gigs/142819271/original/09dafa4104fa6aeca4e62f33326be4933ae7ccac/create-cartoon-profile-picture-abd7.jpg",
          status: "break", // ✅ yellow
        },
        {
          name: "Anna White",
          image: "https://i.pinimg.com/736x/d0/aa/4f/d0aa4f545fec0dd414940cdc0ef7c24f.jpg",
          status: "time-in",
        },
        {
          name: "Peter Green",
          image: "https://imgproxy.attic.sh/dmkTirIVzeVniSoZR6I7La6WsBxANxZk4LnYBiDiouI/s:1200:570::1:ce/t:1:FF00FF:false:false/pd:30:0:30:0/wm:1:nowe:45:45/wmu:aHR0cHM6Ly9hdHRpYy5zaC9fc3RhdGljL2Vtb2ppcy1vcGVuZ3JhcGgvYXBwbGUtdG91Y2gtaWNvbi0xODB4MTgwLXByZWNvbXBvc2VkLnBuZw/wms:96:96/aHR0cHM6Ly9hdHRp/Yy5zaC9qejBiazlm/dDJnOHd3M2d5MmJv/YzZyYzYwYnJh.jpg",
          status: "break",
        },
        {
          name: "Carl Llemos",
          image: "https://img.freepik.com/premium-wektory/dziewczyna-kreskowka-rysunek-reka-ladny-rysunek-dziewczyny-do-zdjecia-profilowego_488586-714.jpg?w=2000",
          status: "break",
        },
      ],
    };
  },
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.view-items {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;

  .view-item {
    border-radius: 12px;
    position: relative;
    margin: 0.5rem;

    .status-indicator {
      position: absolute;
      top: 12px;
      right: 6px;
      height: 16px;
      width: 16px;
      border-radius: 50%;
      border: 2px solid $primary;
    }

    img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border: 2px solid $primary;
    }

    .name {
      font-size: 10px;
    }
  }
}
</style>
