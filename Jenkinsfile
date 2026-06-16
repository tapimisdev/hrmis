pipeline {
    agent any

    triggers {
        githubPush()
    }

    options {
        disableConcurrentBuilds()
        timestamps()
    }

    environment {
        APP_SERVER  = 'mis_server1@192.168.2.228'
        APP_SERVER2 = 'mis_server2@192.168.2.232'

        SSH_KEY = '/var/jenkins_home/.ssh/id_ed25519'

        REPO_SSH_URL = 'git@github.com:tapimisdev/hrmis.git'
        APP_DIR = '/var/www/orbit'
        CONTAINER = 'orbit'
        BRANCH = 'main'
    }

    stages {
        stage('Checkout SCM') {
            steps {
                checkout scm
            }
        }

        stage('Set Build Name') {
            steps {
                script {
                    def msg = sh(
                        script: "git log -1 --pretty=%s",
                        returnStdout: true
                    ).trim()

                    def author = sh(
                        script: "git log -1 --pretty=%an",
                        returnStdout: true
                    ).trim()

                    currentBuild.displayName = "#${env.BUILD_NUMBER} - ${msg}"
                    currentBuild.description = "Triggered by: ${author}"
                }
            }
        }

        stage('Show Build Info') {
            steps {
                sh '''
                    echo "=============================="
                    echo "🚀 Jenkins SCM Deployment"
                    echo "Build Number: $BUILD_NUMBER"
                    echo "Branch: $BRANCH"
                    echo "Workspace: $WORKSPACE"
                    echo "Commit:"
                    git log -1 --oneline
                    echo "Author:"
                    git log -1 --pretty='%an <%ae>'
                    echo "App Dir: $APP_DIR"
                    echo "Container: $CONTAINER"
                    echo "=============================="
                '''
            }
        }

        stage('Prepare SSH Known Hosts') {
            steps {
                sh '''
                    set -euo pipefail

                    mkdir -p ~/.ssh

                    APP_HOST=$(echo "$APP_SERVER" | cut -d@ -f2)
                    APP_HOST2=$(echo "$APP_SERVER2" | cut -d@ -f2)

                    ssh-keyscan -H "$APP_HOST" >> ~/.ssh/known_hosts
                    ssh-keyscan -H "$APP_HOST2" >> ~/.ssh/known_hosts

                    chmod 700 ~/.ssh
                    chmod 600 ~/.ssh/known_hosts
                    chmod 600 "$SSH_KEY"
                '''
            }
        }

        stage('Test SSH Connections') {
            parallel {
                stage('App Server 1') {
                    steps {
                        sh '''
                            set -euo pipefail

                            ssh -i "$SSH_KEY" \
                                -o IdentitiesOnly=yes \
                                -o StrictHostKeyChecking=yes \
                                "$APP_SERVER" "hostname && whoami"
                        '''
                    }
                }

                stage('App Server 2') {
                    steps {
                        sh '''
                            set -euo pipefail

                            ssh -i "$SSH_KEY" \
                                -o IdentitiesOnly=yes \
                                -o StrictHostKeyChecking=yes \
                                "$APP_SERVER2" "hostname && whoami"
                        '''
                    }
                }
            }
        }

        stage('Deploy Production') {
            parallel {
                stage('Deploy App Server 1') {
                    steps {
                        deployLaravel("${APP_SERVER}", "mis_server1", true)
                    }
                }

                stage('Deploy App Server 2') {
                    steps {
                        deployLaravel("${APP_SERVER2}", "mis_server2", false)
                    }
                }
            }
        }
    }

    post {
        success {
            echo '✅ Deploy done on both app servers!'
        }

        failure {
            echo '❌ Deploy failed. Check Jenkins console logs.'
        }
    }
}

def deployLaravel(String server, String linuxUser, boolean runMigration) {
    sh """
        set -euo pipefail

        ssh -i "$SSH_KEY" \\
            -o IdentitiesOnly=yes \\
            -o StrictHostKeyChecking=yes \\
            "${server}" "
                set -euo pipefail

                echo '================================'
                echo '🚀 Deploying to ${server}'
                echo '================================'

                test -d '${APP_DIR}/.git' || {
                    echo 'ERROR: ${APP_DIR} is not a git repo'
                    exit 1
                }

                cd '${APP_DIR}'

                git remote set-url origin '${REPO_SSH_URL}'

                export GIT_SSH_COMMAND='ssh -i /home/${linuxUser}/.ssh/id_ed25519 -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new'

                echo 'Fetching latest code...'
                git fetch origin '${BRANCH}'

                echo 'Resetting to origin/${BRANCH}...'
                git reset --hard 'origin/${BRANCH}'
                git clean -fd

                echo 'Current deployed commit:'
                git log -1 --oneline

                echo 'Clearing Laravel cache...'
                docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && php artisan optimize:clear'

                echo 'Installing PHP dependencies...'
                docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev'

                echo 'Installing Node dependencies...'
                docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && npm ci'

                echo 'Building frontend assets...'
                docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && npm run build'

                echo 'Caching Laravel config/routes/views...'
                docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && php artisan optimize:clear'

                ${runMigration ? "echo 'Running database migrations...'; docker exec '${CONTAINER}' bash -lc 'cd /var/www/html && php artisan migrate --force'" : "echo 'Skipping migration on this server...'"}

                echo '✅ Deploy done on ${server}'
            "
    """
}