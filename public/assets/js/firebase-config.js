/* =========================================================================
   Firebase konfiqurasiyası
   -------------------------------------------------------------------------
   BURANI DOLDUR: Firebase Console → Project settings → Your apps → Web app
   (Flutter tətbiqin ilə EYNİ layihəni seç ki, veb qeydiyyat eyni bazaya düşsün.)

   Vacib addımlar:
   1) Authentication → Sign-in method → Phone  — aktiv et.
   2) Authentication → Settings → Authorized domains — yolyodasi.az əlavə et.
   3) Firestore → Rules — "users" kolleksiyasına öz uid-i üçün yazma icazəsi ver.
   4) Test üçün: Authentication → Phone → Test phone numbers (SMS sərf olunmur).

   Konfiqurasiya boş qalarsa sayt DEMO rejimdə işləyir:
   forma tam işləyir, kod göndərilmir, istənilən 6 rəqəm qəbul edilir.
   ========================================================================= */

export const firebaseConfig = {
  apiKey: "AIzaSyCQjptypQSJBWiqSpXjadH9fI_-w2ovOVw",
  authDomain: "yolyoldase.firebaseapp.com",
  projectId: "yolyoldase",
  storageBucket: "yolyoldase.firebasestorage.app",
  messagingSenderId: "52976967147",
  appId: "1:52976967147:web:5dac0f375f3740d0098dae",
  measurementId: "G-2PSQCLV4WE"
};




/** Firestore-da istifadəçi sənədlərinin kolleksiyası (Flutter tətbiqi ilə eyni olmalıdır) */
export const USERS_COLLECTION = "users";

/** Telefon nömrəsinin ölkə kodu */
export const COUNTRY_CODE = "+994";
