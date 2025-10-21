@extends('admin.layouts.app')
<style>

    
  h1 {
    text-align: center;
    margin-bottom: 50px;
    color: #2c3e50;
    font-weight: 700;
    font-size: 2.6rem;
    letter-spacing: 1.5px;
  }

  /* Tree wrapper with horizontal scroll & draggable */
  .tree-wrapper {
    overflow-x: auto;
    cursor: grab;
    padding-bottom: 20px;
    -webkit-overflow-scrolling: touch; /* smooth scrolling on iOS */
    white-space: nowrap; /* prevent wrapping */
  }

  /* Tree container is horizontal flex, no wrap */
  .tree {
    display: flex;
    flex-wrap: nowrap; /* no wrapping */
    gap: 60px;
    min-width: max-content; /* allow scroll */
  }

  /* Agency Box */
  .agency {
    background: #fff;
    border-radius: 20px;
    min-width: 320px;
    max-width: 360px;
    padding: 30px 35px 50px;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    vertical-align: top;
    display: inline-flex; /* to respect white-space nowrap */
  }

  .agency > h2 {
    color: #032985;
    font-size: 2rem;
    margin-bottom: 35px;
    font-weight: 900;
    letter-spacing: 1px;
    position: relative;
  }

  /* Vertical branch line for agency */
  .agency::before {
    content: '';
    position: absolute;
    top: 75px;
    left: 50px;
    width: 4px;
    height: calc(100% - 75px);
    background: linear-gradient(180deg, #032985, #34539B);
    border-radius: 3px;
    animation: drawLine 1s ease forwards;
    opacity: 0;
    animation-delay: 0.3s;
  }

  /* Levels container */
  .level {
    background: #eafaf1;
    border-radius: 18px;
    padding: 25px 35px 40px 60px;
    margin-bottom: 40px;
    width: 100%;
    position: relative;
    box-shadow: inset 5px 0 0 #032985;
  }

  .level:last-child {
    margin-bottom: 0;
  }

  /* Level Title */
  .level > h3 {
    color: #1e8449;
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 25px;
    padding-left: 35px;
    position: relative;
    letter-spacing: 0.04em;
  }

  /* Circle node before level title */
  .level > h3::before {
    content: '';
    position: absolute;
    left: 0;
    top: 9px;
    width: 20px;
    height: 20px;
    background: #34539B;
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(46,204,113,0.7);
  }

  /* Person nodes container - vertical stack */
  .person {
    background: #fff;
    border-radius: 14px;
    padding: 18px 25px;
    margin-bottom: 20px;
    box-shadow:
      0 5px 15px rgba(39,174,96,0.15);
    position: relative;
    cursor: default;
    transition: transform 0.4s ease, box-shadow 0.4s ease, background 0.4s ease;
    display: flex;
    flex-direction: column;
  }

  /* Hover effect on person */
  .person:hover {
    background: #032985;
    color: #ecf0f1;
    box-shadow:
      0 12px 30px rgba(39,174,96,0.4);
    transform: translateY(-6px);
  }
  .person:hover strong,
  .person:hover span {
    color: #ecf0f1;
  }

  .person strong {
    font-weight: 800;
    font-size: 1.1rem;
    margin-bottom: 6px;
  }

  .person span {
    font-size: 0.9rem;
    color: #555;
    transition: color 0.4s ease;
  }

  /* Connector lines between levels and persons */

  /* Horizontal line from vertical branch to person */
  .person::before {
    content: '';
    position: absolute;
    left: -55px;
    top: 50%;
    transform: translateY(-50%);
    width: 55px;
    height: 4px;
    background: linear-gradient(90deg, #032985, #34539B);
    border-radius: 3px;
    opacity: 0;
    animation: drawHorizontalLine 0.8s ease forwards;
  }

  /* First person in each level gets a vertical line connecting to branch */
  .person:first-child::after {
    content: '';
    position: absolute;
    left: -55px;
    top: 0;
    width: 4px;
    height: 50%;
    background: linear-gradient(180deg, #032985, #34539B);
    border-radius: 3px;
    opacity: 0;
    animation: drawVerticalHalfLine 0.8s ease forwards;
    animation-delay: 0.4s;
  }

  /* Animations for lines */
  @keyframes drawLine {
    from { height: 0; opacity: 0; }
    to { height: calc(100% - 75px); opacity: 1; }
  }

  @keyframes drawHorizontalLine {
    from { width: 0; opacity: 0; }
    to { width: 55px; opacity: 1; }
  }

  @keyframes drawVerticalHalfLine {
    from { height: 0; opacity: 0; }
    to { height: 50%; opacity: 1; }
  }

  /* Responsive tweaks (only fonts/padding) */
  @media (max-width: 480px) {
    h1 {
      font-size: 2rem;
      padding: 0 10px;
    }

    .agency {
      padding: 20px 20px 40px 45px;
    }

    .level {
      padding-left: 40px;
    }

    .person {
      padding: 14px 18px;
    }

    .person::before {
      left: -35px;
      width: 35px;
    }
    .person:first-child::after {
      left: -35px;
      height: 40%;
    }
  }
</style>
@section('content')
    <div class="container pt-4 px-3">
        <x-header title="All Approvers" subtitle="View all approvers in graphically">
            <x-button-link 
                :href="route('settings.approvers.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div class="my-4">
            <div class="tree-wrapper" id="tree-wrapper">
                <div class="tree" id="tree-container">
                    <!-- Tree will be generated here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
 $(function() {
    const agencies = {
        'Agency 1': {
            'Level 1': [
            { name: 'Alice Johnson', profile: 'Manager' },
            { name: 'Bob Smith', profile: 'Team Lead' }
            ],
            'Level 2': [
            { name: 'Charlie Brown', profile: 'Senior Dev' },
            { name: 'Dana White', profile: 'Dev' },
            { name: 'Ethan Clark', profile: 'QA Engineer' }
            ],
            'Level 3': [
            { name: 'Eve Adams', profile: 'Intern' },
            { name: 'Fiona Davis', profile: 'Intern' }
            ]
        },
        'Agency 2': {
            'Level 1': [
            { name: 'Frank Miller', profile: 'Director' },
            { name: 'George King', profile: 'Assistant Director' }
            ],
            'Level 2': [
            { name: 'Grace Lee', profile: 'HR' },
            { name: 'Hannah Scott', profile: 'Recruiter' }
            ],
            'Level 3': [
            { name: 'Ian Martinez', profile: 'Intern' }
            ]
        },
        'Agency 3': {
            'Level 1': [
            { name: 'Jack Wilson', profile: 'General Manager' },
            { name: 'Karen Thomas', profile: 'Operations Lead' }
            ],
            'Level 2': [
            { name: 'Liam Moore', profile: 'Senior Engineer' },
            { name: 'Mia Robinson', profile: 'Engineer' },
            { name: 'Noah Taylor', profile: 'Engineer' }
            ],
            'Level 3': [
            { name: 'Olivia Walker', profile: 'Junior Engineer' },
            { name: 'Paul Harris', profile: 'Junior Engineer' }
            ],
            'Level 4': [
            { name: 'Quinn Adams', profile: 'Intern' }
            ]
        },
        'Agency 4': {
            'Level 1': [
            { name: 'Rachel Young', profile: 'CEO' }
            ],
            'Level 2': [
            { name: 'Samuel Allen', profile: 'CFO' },
            { name: 'Tina Baker', profile: 'COO' }
            ],
            'Level 3': [
            { name: 'Uma Carter', profile: 'Senior Analyst' },
            { name: 'Victor Evans', profile: 'Analyst' }
            ],
            'Level 4': [
            { name: 'Wendy Foster', profile: 'Intern' }
            ]
        }
    };

    const $container = $('#tree-container');

    $.each(agencies, function(agencyName, levels) {
      const $agencyDiv = $('<div>').addClass('agency');
      $agencyDiv.append($('<h2>').text(agencyName));

      $.each(levels, function(levelName, people) {
        const $levelDiv = $('<div>').addClass('level');
        $levelDiv.append($('<h3>').text(levelName));

        people.forEach(person => {
          const $personDiv = $('<div>').addClass('person');
          $personDiv.append($('<strong>').text(person.name));
          $personDiv.append($('<span>').text(person.profile));
          $levelDiv.append($personDiv);
        });

        $agencyDiv.append($levelDiv);
      });

      $container.append($agencyDiv);
    });
  });

  // Draggable horizontal scroll
  const treeWrapper = document.getElementById('tree-wrapper');
  let isDragging = false;
  let startX;
  let scrollLeft;


  treeWrapper.addEventListener('mousedown', (e) => {
    isDragging = true;
    startX = e.pageX - treeWrapper.offsetLeft;
    scrollLeft = treeWrapper.scrollLeft;
    treeWrapper.style.cursor = 'grabbing';
    console.log(1);
  });

  treeWrapper.addEventListener('mouseleave', () => {
    isDragging = false;
    treeWrapper.style.cursor = 'grab';
    console.log(1);
  });

  treeWrapper.addEventListener('mouseup', () => {
    isDragging = false;
    treeWrapper.style.cursor = 'grab';
    console.log(2);
  });

  treeWrapper.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    e.preventDefault();
    const x = e.pageX - treeWrapper.offsetLeft;
    const walk = (x - startX) * 2; // scroll speed multiplier
    treeWrapper.scrollLeft = scrollLeft - walk;
  });
</script>
@endsection

