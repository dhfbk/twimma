import axios from "axios";

export default axios.create({
    baseURL: "https://dh-server.fbk.eu/mt-ann/",
    timeout: 300000,
});
