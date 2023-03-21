<template>
    <h1>Tweet annotation</h1>
    <div v-if="!loaded">
        <span class="spinner-border spinner-border-sm me-3"></span>
        Loading...
    </div>
    <div v-else-if="!tweetOk">
        <p>
            No tweets to annotate.
        </p>
        <p><button class="btn btn-danger ms-3" type="button" @click="undo()">Undo last</button></p>
    </div>
    <div v-else class="row">
        <div id="tweet-container" class="col-lg-6">
            <blockquote class="twitter-tweet">
                <span class="spinner-border spinner-border-sm me-3"></span>
                <a :href="'https://twitter.com/' + tweetInfo.author + '/status/' + tweetInfo.tweet_id">
                    Tweet is loading
                </a>
            </blockquote>
        </div>
        <div class="col-lg-6 mt-lg-0 mt-4">
            <form @submit.prevent="submit()">
                <p><strong>Is this tweet sexist?</strong></p>
                <div class="form-check form-check-inline">
                <input v-model="answer.isSexist" class="form-check-input" type="radio" name="isSexist" id="inlineRadioSexist1" value="1">
                <label class="form-check-label" for="inlineRadioSexist1">Yes, explicitly</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isSexist" class="form-check-input" type="radio" name="isSexist" id="inlineRadioSexist2" value="2">
                <label class="form-check-label" for="inlineRadioSexist2">Yes, implicitly</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isSexist" class="form-check-input" type="radio" name="isSexist" id="inlineRadioSexist3" value="3">
                <label class="form-check-label" for="inlineRadioSexist3">No, it is not</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isSexist" class="form-check-input" type="radio" name="isSexist" id="inlineRadioSexist4" value="4">
                <label class="form-check-label" for="inlineRadioSexist4">Not sure</label>
                </div>

                <p class="mt-4"><strong>Does the tweet contain toxic masculinity?</strong></p>
                <div class="form-check form-check-inline">
                <input v-model="answer.isMasc" class="form-check-input" type="radio" name="isMasc" id="inlineRadioMasc1" value="1">
                <label class="form-check-label" for="inlineRadioMasc1">Yes, explicitly</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isMasc" class="form-check-input" type="radio" name="isMasc" id="inlineRadioMasc2" value="2">
                <label class="form-check-label" for="inlineRadioMasc2">Yes, implicitly</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isMasc" class="form-check-input" type="radio" name="isMasc" id="inlineRadioMasc3" value="3">
                <label class="form-check-label" for="inlineRadioMasc3">No, it does not</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isMasc" class="form-check-input" type="radio" name="isMasc" id="inlineRadioMasc4" value="4">
                <label class="form-check-label" for="inlineRadioMasc4">Not sure</label>
                </div>

                <p class="mt-4">
                    <strong>Is this tweet inappropriate to be shown to students (e.g. vulgar/rude, show explicit content, etc.)?</strong>
                </p>
                <div class="form-check form-check-inline">
                <input v-model="answer.isInapp" class="form-check-input" type="radio" name="isInapp" id="inlineRadioInapp1" value="1">
                <label class="form-check-label" for="inlineRadioInapp1">Yes, it is</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isInapp" class="form-check-input" type="radio" name="isInapp" id="inlineRadioInapp2" value="2">
                <label class="form-check-label" for="inlineRadioInapp2">Not sure</label>
                </div>
                <div class="form-check form-check-inline">
                <input v-model="answer.isInapp" class="form-check-input" type="radio" name="isInapp" id="inlineRadioInapp3" value="3">
                <label class="form-check-label" for="inlineRadioInapp3">No, it is not</label>
                </div>

                <p class="mt-4">
                    <strong>Notes/comments:</strong>
                </p>
                <div>
                    <textarea v-model="answer.notes" class="form-control" id="notes" rows="3"></textarea>
                </div>

                <p class="mt-4 text-end">
                    <button class="btn btn-danger me-3" type="button" @click="undo()">Undo last</button>
                    <button class="btn btn-primary" :disabled="buttonDisabled" type="submit">
                        Submit
                        <span v-if="buttonDisabled" class="spinner-border spinner-border-sm me-3"></span>
                    </button>
                </p>
            </form>
        </div>
    </div>
</template>

<script setup>
/* eslint-disable */

import { onMounted, inject, ref } from 'vue';

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');

const loaded = ref(false);
const tweetOk = ref(false);
const buttonDisabled = ref(true);

const skipped = ref(0);

const tweetInfo = ref({})
const answerReset = ref({
    notes: "",
    isSexist: 0,
    isMasc: 0,
    isInapp: 0
});
const answer = ref({... answerReset.value});

// See: https://stackoverflow.com/questions/28550328/referenceerror-twttr-is-not-defined-even-while-using-twttr-ready
window.twttr = (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0],
    t = window.twttr || {};
    if (d.getElementById(id)) return t;
    js = d.createElement(s);
    js.id = id;
    js.src = "https://platform.twitter.com/widgets.js";
    fjs.parentNode.insertBefore(js, fjs);

    t._e = [];
    t.ready = function(f) {
    t._e.push(f);
    };

    return t;
}(document, "script", "twitter-wjs"));

function undo() {
    if (confirm("Are you sure? This action is irreversible!")) {
        loaded.value = false;
        axios.post("?", {"action": "unDoLast", ...updateAxiosParams()})
        .then((response) => {
            // getNewTweet();
        })
        .catch((reason) => {
            // getNewTweet();
        })
        .then(() => {
            getNewTweet();
        });
    }
}

async function getNewTweet() {
    buttonDisabled.value = true;
    await axios.get("?", {"params": {"action": "getNextTweet", ...updateAxiosParams()}})
        .then((response) => {
            loaded.value = true;
            tweetOk.value = true;
            answer.value = {... answerReset.value};
            tweetInfo.value = response.data.data;
            window.twttr.widgets.load(document.getElementById("tweet-container"))
            setTimeout(function() {
                let l = $(".twitter-tweet-rendered").length;
                if (l == 0) {
                    skipped.value += 1;
                    if (skipped.value > 3) {
                        alert("Skipped 3 tweets, stopping. Please refresh the page.");
                    }
                    else {
                        axios.post("?", {"action": "skipTweet", tid: tweetInfo.value.id, ...updateAxiosParams()})
                            .then((response) => {
                                loaded.value = false;
                                console.log(response);
                                getNewTweet();
                            })
                            .catch((reason) => {
                                if (reason.response) {
                                    alert(reason.response.data.error);
                                }
                            })
                            .then(() => {
                            });
                    }
                }
                else {
                    skipped.value = 0;
                    buttonDisabled.value = false;
                }
            }, 2000);
        })
        .catch((reason) => {
            loaded.value = true;
            tweetOk.value = false;
            if (reason.response) {
                alert(reason.response.data.error);
            }
        })
        .then(() => {
            // mainLoaded.value = true;
        });
}

onMounted(async function() {
    await getNewTweet();
});

function submit() {
    buttonDisabled.value = true;
    axios.post("?", {"action": "saveAnswer", tid: tweetInfo.value.id, answer: answer.value, ...updateAxiosParams()})
        .then((response) => {
            loaded.value = false;
            console.log(response);
            getNewTweet();
        })
        .catch((reason) => {
            if (reason.response) {
                alert(reason.response.data.error);
            }
        })
        .then(() => {
        });
}
</script>

