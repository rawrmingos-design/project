
document.addEventListener("DOMContentLoaded", function () {
   var e = document.getElementById("user_id").value,
       t = window.location.href;

   function saveUserId(key) {
       document.getElementById("user_id").addEventListener("input", function () {
           e = this.value;
           localStorage.setItem(key, e);
       });
       var n = localStorage.getItem(key);
       if (n !== null) {
           document.getElementById("user_id").value = n;
       }
   }

   function saveUserIdAndZone(keyId, keyZone) {
       document.getElementById("user_id").addEventListener("input", function () {
           e = this.value;
           localStorage.setItem(keyId, e);
       });
       document.getElementById("zone").addEventListener("input", function () {
           var t = this.value;
           localStorage.setItem(keyZone, t);
       });
       var d = localStorage.getItem(keyId),
           i = localStorage.getItem(keyZone);
       if (d !== null) {
           document.getElementById("user_id").value = d;
       }
       if (i !== null) {
           document.getElementById("zone").value = i;
       }
   }

   if (t.includes("/id/mobile-legends")) {
       saveUserIdAndZone(t + "_id", t + "_zone");
   } else if (t.includes("/id/free-fire")) {
       saveUserId(t + "_id");
   } else if (t.includes("/id/genshin-impact")) {
       saveUserId(t + "_id");
   } else if (t.includes("/id/pubg-mobile")) {
       saveUserId(t + "_id");
   } else if (t.includes("/id/valorant")) {
       saveUserId(t + "_id");
   } else if (t.includes("/id/call-of-duty")) {
       saveUserId(t + "_id");
   } else if (t.includes("/id/royal-dream")) {
       saveUserId(t + "_id");
   }
});