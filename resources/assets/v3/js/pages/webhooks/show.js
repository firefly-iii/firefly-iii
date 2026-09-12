/*
 * show.js
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import "../../boot/bootstrap.js";
import sidebar from "../../pages/shared/sidebar.js";
import dates from "../shared/dates.js";
import format from "date-fns/format";
import i18next from "i18next";
import Post from "../../api/model/webhook/post.js";
import Get from "../../api/model/webhook/get.js";
import Put from "../../api/model/webhook/put.js";
import Alpine from "alpinejs";

window.enableDates = false;

let show = function () {
    return {
        i18next: null,
        loading: true,

        // webhook data
        id: 0,
        active: false,
        show_secret: false,

        title: "",
        url: "",
        secret: "",

        triggers: [],
        responses: [],
        deliveries: [],
        message_attempts: [],
        message_content: "",
        messages: [],
        edit_url: "#",
        delete_url: "#",
        success_message: "",
        disabledTrigger: false,
        init() {
            this.i18next = i18next;
            this.getWebhook();
        },
        getWebhook() {
            this.loading = true;
            const page = window.location.href.split("/");
            this.id = parseInt(page[page.length - 1]);
            this.downloadWebhook();
            this.downloadWebhookMessages();
        },
        toggleSecret: function () {
            this.show_secret = !this.show_secret;
        },
        submitTest: function (e) {
            if (e) {
                e.preventDefault();
            }
            let journalId = parseInt(prompt("Enter a transaction ID"));
            if (journalId !== null && journalId > 0 && journalId <= 16777216) {
                this.disabledTrigger = true;
                this.success_message = i18next.t(
                    "firefly.webhook_was_triggered",
                );
                new Post().triggerTransaction(this.id, journalId);

                // set a time-outs.
                this.loading = true;
                setTimeout(() => {
                    this.getWebhook();
                    this.disabledTrigger = false;
                }, 2000);
                // console.log('OK 3');
            }

            return false;
        },

        downloadWebhook: function () {
            new Get()
                .show(this.id)
                .then((response) => {
                    this.edit_url = "./webhooks/edit/" + this.id;
                    this.delete_url = "./webhooks/delete/" + this.id;
                    this.title = response.data.data.attributes.title;
                    this.url = response.data.data.attributes.url;
                    this.secret = response.data.data.attributes.secret;
                    this.triggers = response.data.data.attributes.triggers;
                    this.responses = response.data.data.attributes.responses;
                    this.deliveries = response.data.data.attributes.deliveries;
                    this.active = response.data.data.attributes.active;
                    this.url = response.data.data.attributes.url;
                })
                .catch((error) => {
                    this.error_message = error.response.data.message;
                });
        },
        downloadWebhookMessages: function () {
            this.messages = [];
            new Get().messages(this.id, {}).then((response) => {
                for (let i in response.data.data) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = response.data.data[i];
                        this.messages.push({
                            id: current.id,
                            created_at: format(
                                new Date(current.attributes.created_at),
                                i18next.t("config.date_time_fns"),
                            ),
                            uuid: current.attributes.uuid,
                            success:
                                current.attributes.sent &&
                                !current.attributes.errored,
                            message: current.attributes.message,
                        });
                    }
                }
                this.loading = false;
            });
        },
        resetSecret: function () {
            new Put().put({ secret: "anything" }, { id: this.id }).then(() => {
                this.downloadWebhook();
            });
        },

        showWebhookMessage: function (id) {
            new Get().message(this.id, id, {}).then((response) => {
                this.message_content = response.data.data.attributes.message;
            });
        },
        showWebhookAttempts: function (id) {
            console.log("showWebhookAttempts", id);
            this.message_attempts = [];
            new Get().attempts(this.id, id, {}).then((response) => {
                for (let i in response.data.data) {
                    if (Object.hasOwn(response.data.data, i)) {
                        let current = response.data.data[i];
                        this.message_attempts.push({
                            id: current.id,
                            created_at: format(
                                new Date(current.attributes.created_at),
                                i18next.t("config.date_time_fns"),
                            ),
                            logs: current.attributes.logs,
                            status_code: current.attributes.status_code,
                            response: current.attributes.response,
                        });
                    }
                }
            });
        },
    };
};

const comps = {
    show,
    sidebar,
    dates,
};

function loadPage(comps) {
    console.log("loadPage");
    Object.keys(comps).forEach((comp) => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
        //console.log(comp);
    });
    Alpine.start();
}

// wait for load until bootstrapped event is received.
document.addEventListener("firefly-iii-bootstrapped", () => {
    console.log("Loaded through event listener.");
    loadPage(comps);
});
// or is bootstrapped before event is triggered.
if (window.bootstrapped) {
    console.log("Loaded through window variable.");
    loadPage(comps);
}
