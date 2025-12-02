<template>
   <!-- Banner Image -->
    <div class="image-container border">
      <img 
        :src="'/storage/events/attachments/' + data.banner" 
        :alt="data.title"
      >
    </div>

    <!-- Header Section -->
    <div class="header-container">
      <h1 class="announcement-title">
        <span class="horn">📢</span> 
        {{ data.title }}
      </h1>

      <!-- Metadata -->
      <div class="metadata">
        Posted on 
        <span class="metadata-highlight">
          {{
            new Date(data.posted_on || data.created_at)
              .toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
          }}
        </span>
        by 
        <span v-for="postedby in posted_by" :key="postedby.id" class="metadata-highlight">
          {{ postedby }}
        </span>
      </div>

      <!-- Tags -->
      <div class="tags-container">
        <span v-for="tag in tags" :key="tag" class="badge bg-primary">
          {{ tag }}
        </span>
      </div>

      <!-- Description -->
      <div class="description" v-html="data.description"></div>
    </div>
</template>

<script>
export default {
  props: {
    data: Object,
    tags: Object,
    posted_by: Object,
  }
}
</script>

<style scoped lang="scss">

// Banner Image
.image-container {
  overflow: hidden;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  
  img {
    width: 100%;
    height: auto;
    object-fit: cover;
    object-position: top;
    display: block;
  }
}

// Header Section
.header-container {
  padding: 0 0.5rem;
}

.announcement-title {
  font-size: 2rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--bs-body-color);
  margin-bottom: 1rem;
  line-height: 1.3;
  
  .horn {
    margin-right: 0.5rem;
  }
}

.border-sa-top {
  border-top: 1px solid var(--bs-body-color);;
}
// Metadata
.metadata {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--bs-secondary-color);;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin-bottom: 1.5rem;
}

.metadata-highlight {
  color: var(--bs-body-color);
  text-decoration: underline;
  text-decoration-color: var(--bs-secondary-color);;
  text-underline-offset: 2px;
}

// Tags
.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 2rem;
  
  .badge {
    text-transform: uppercase;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    letter-spacing: 0.5px;
  }
}

// Description
.description {
  font-size: 1rem;
  line-height: 1.7;
  color: var(--bs-body-color);;
  margin-bottom: 2rem;
}
</style>