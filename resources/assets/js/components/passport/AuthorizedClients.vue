<style scoped>
    .action-link {
        cursor: pointer;
    }

    .m-b-none {
        margin-bottom: 0;
    }
</style>

<template>
    <div>
        <div v-if="tokens.length > 0">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Authorized Applications
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Authorized Tokens -->
                    <table class="table table-borderless m-b-none">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Scopes</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr v-for="token in tokens">
                            <!-- Client Name -->
                            <td style="vertical-align: middle;">
                                {{ token.client.name }}
                            </td>

                            <!-- Scopes -->
                            <td style="vertical-align: middle;">
                                    <span v-if="token.scopes.length > 0">
                                        {{ token.scopes.join(', ') }}
                                    </span>
                            </td>

                            <!-- Revoke Button -->
                            <td style="vertical-align: middle;">
                                <a class="action-link btn btn-danger btn-xs" @click="revoke(token)">
                                    Revoke
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                tokens: []
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        methods: {
            /**
             * Prepare the component (Vue 2.x).
             */
            prepareComponent() {
                this.getTokens();
            },

            /**
             * Get all of the authorized tokens for the user.
             */
            getTokens() {
                axios.get('/oauth/tokens')
                    .then(response => {
                        this.tokens = response.data;
                    });
            },

            /**
             * Revoke the given token.
             */
            revoke(token) {
                axios.delete('/oauth/tokens/' + token.id)
                    .then(response => {
                        this.getTokens();
                    });
            }
        }
    }
</script>
