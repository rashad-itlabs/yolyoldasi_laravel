<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Saytın ilkin məzmununu bazaya köçürür.
 *
 * Mətnlər əvvəllər blade fayllarında sabit yazılmışdı — buradakı nüsxə
 * həmin an mövcud olan variantdır. Seeder idempotentdir: mövcud sətirlərin
 * üzərinə yazmır, yalnız çatışmayanı əlavə edir, ona görə istismarda
 * təkrar çağırmaq admin redaktələrini silmir.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedPages();
        $this->seedFaqs();
    }

    private function seedSettings(): void
    {
        $defaults = [
            'contact.email_general'  => 'info@yolyodasi.az',
            'contact.email_support'  => 'destek@yolyodasi.az',
            'contact.email_privacy'  => 'mexfilik@yolyodasi.az',
            'contact.phone'          => '',
            'contact.address'        => 'Bakı, Azərbaycan',
            'contact.response_time'  => 'Müraciətlərə iş günləri ərzində 24 saat içində cavab veririk.',
            'contact.urgent_note'    => 'Təcili təhlükəsizlik məsələləri üçün dəstək ünvanına “TƏCİLİ” mövzusu ilə yaz.',
            'social.instagram'       => '',
            'social.facebook'        => '',
            'social.whatsapp'        => '',
            'site.footer_about'      => 'Azərbaycanda şəhərlərarası səfər paylaşımı platforması. Boş oturacağı dəyərləndir, yol xərcini bölüş.',
        ];

        $existing = \App\Models\SiteSetting::pluck('key')->all();

        foreach ($defaults as $key => $value) {
            if (in_array($key, $existing, true)) {
                continue;
            }

            $group = str_contains($key, '.') ? strtok($key, '.') : 'general';

            Settings::set($key, $value, $group);
        }
    }

    private function seedPages(): void
    {
        foreach ($this->pages() as $slug => $data) {
            Page::firstOrCreate(['slug' => $slug], $data);
        }
    }

    private function seedFaqs(): void
    {
        if (Faq::exists()) {
            return;
        }

        foreach ($this->faqItems() as $i => $item) {
            Faq::create($item + ['sort_order' => ($i + 1) * 10, 'is_active' => true]);
        }
    }

    private function pages(): array
    {
        return [
            'terms' => [
                'title'              => 'İstifadə Qaydaları',
                'lead'               => <<<'L'
Platformadan istifadə edərkən hər iki tərəfin hüquq və öhdəliklərini müəyyən edən şərtlər.
L,
                'content_updated_at' => '2026-09-13',
                'is_published'       => true,
                'body'               => <<<'BODY'
<h2 id="umumi">1. Ümumi müddəalar</h2>
<p>Bu İstifadə Qaydaları <strong>Yol Yoldaşı</strong> mobil tətbiqi və <strong>yolyodasi.az</strong> saytından istifadə şərtlərini müəyyən edir. Qeydiyyatdan keçməklə bu qaydaları tam və qeyd-şərtsiz qəbul etmiş sayılırsan.</p>
<p>Platformadan istifadə edə bilməyin üçün <strong>ən azı 18 yaşın</strong> olmalı və düzgün, aktual məlumat təqdim etməlisən.</p>

<h2 id="xidmet">2. Xidmətin mahiyyəti</h2>
<div class="callout callout--warn">
  <p><strong>Vacib:</strong> Yol Yoldaşı daşıma xidməti göstərmir. Biz yalnız eyni istiqamətə gedən sürücü və sərnişinləri bir araya gətirən <strong>texnoloji platformayıq</strong>. Səfər müqaviləsi birbaşa sürücü ilə sərnişin arasında bağlanır.</p>
</div>
<p>Platforma sərnişindaşıma fəaliyyəti ilə məşğul olmur, sürücüləri işə götürmür və onların adından çıxış etmir. Elanların məzmununa və səfərin gedişatına görə məsuliyyət müvafiq istifadəçinin üzərindədir.</p>

<h2 id="hesab">3. Hesab və qeydiyyat</h2>
<ul>
  <li>Hesab telefon nömrəsinin SMS kodu ilə təsdiqi əsasında yaradılır.</li>
  <li>Bir şəxs yalnız <strong>bir hesab</strong> yarada bilər. Saxta ad və ya başqasının məlumatları ilə qeydiyyat qadağandır.</li>
  <li>Hesabının təhlükəsizliyinə və onun vasitəsilə edilən bütün əməliyyatlara görə məsuliyyət sənin üzərindədir.</li>
  <li>SMS təsdiq kodunu heç kimlə paylaşmamalısan.</li>
</ul>

<h2 id="surucu">4. Sürücünün öhdəlikləri</h2>
<ul>
  <li>Etibarlı <strong>sürücülük vəsiqəsinə</strong>, qeydiyyat şəhadətnaməsinə və qüvvədə olan <strong>icbari sığortaya</strong> sahib olmaq.</li>
  <li>Avtomobilin texniki cəhətdən saz və təhlükəsiz olmasını təmin etmək.</li>
  <li>Elanda göstərilən marşrut, tarix, saat, yer sayı və qiymətə <strong>sadiq qalmaq</strong>.</li>
  <li>Yol hərəkəti qaydalarına riayət etmək; spirtli içki və ya sərxoşedici maddələrin təsiri altında sükan arxasına keçməmək.</li>
  <li>Elan edilən qiyməti sərnişin razılığı olmadan səfər zamanı dəyişməmək.</li>
  <li>Təqdim etdiyi sənədlərin həqiqi olmasına zəmanət vermək.</li>
</ul>

<h2 id="sernisin">5. Sərnişinin öhdəlikləri</h2>
<ul>
  <li>Razılaşdırılmış görüş nöqtəsində <strong>vaxtında</strong> olmaq.</li>
  <li>Bron etdiyi yer sayına uyğun gəlmək; əlavə sərnişin və ya böyük yük barədə əvvəlcədən məlumat vermək.</li>
  <li>Səfər haqqını razılaşdırılmış qaydada ödəmək.</li>
  <li>Avtomobilə və digər sərnişinlərə hörmətlə yanaşmaq, salonu təmiz saxlamaq.</li>
  <li>Gedə bilməyəcəyi halda bronu <strong>mümkün qədər tez</strong> ləğv etmək.</li>
</ul>

<h2 id="bron">6. Elan, bron və ləğv</h2>
<ul>
  <li>Bir elanda maksimum <strong>4 boş yer</strong> göstərilə bilər.</li>
  <li>Bron sürücünün təsdiqindən sonra qüvvəyə minir. Sürücü səbəb göstərmədən bronu rədd edə bilər.</li>
  <li>Telefon nömrələri yalnız bron təsdiqləndikdən sonra qarşılıqlı açılır.</li>
  <li>Hər iki tərəf səfərə qədər bronu ləğv edə bilər; qarşı tərəfə dərhal bildiriş göndərilir.</li>
  <li><strong>Sistematik ləğv</strong> və ya səfərə gəlməmək halları reytinqin azalmasına, təkrarlandıqda isə hesabın məhdudlaşdırılmasına səbəb olur.</li>
</ul>

<h2 id="odenis">7. Ödəniş və xərc bölüşdürmə</h2>
<p>Platforma <strong>xərc bölüşdürmə</strong> prinsipi üzərində qurulub: sürücünün təyin etdiyi qiymət yanacaq, amortizasiya və yol xərclərinin sərnişinlərlə bölüşdürülməsini nəzərdə tutur və <strong>gəlir əldə etmək məqsədi daşımamalıdır</strong>.</p>
<ul>
  <li>Ödəniş sürücü ilə sərnişin arasında birbaşa aparılır; Platforma ödənişdə iştirak etmir və komissiya tutmur.</li>
  <li>Qeydiyyat, elan yerləşdirmə və bron xidmətləri pulsuzdur.</li>
  <li>Ödənişlə bağlı mübahisələrdə Platforma tərəf deyil, lakin şikayət əsasında hesablara qarşı tədbir görə bilər.</li>
</ul>

<h2 id="qadagan">8. Qadağan olunan davranış</h2>
<ul>
  <li>Saxta elan, saxta profil və ya başqasının adından istifadə.</li>
  <li>Qanunla qadağan olunmuş əşyaların, silah və ya narkotik vasitələrin daşınması.</li>
  <li>Digər istifadəçilərə qarşı təhqir, hədə, təzyiq, ayrı-seçkilik və ya cinsi xarakterli davranış.</li>
  <li>Platformadan kommersiya taksi fəaliyyəti və ya reklam məqsədilə istifadə.</li>
  <li>Digər istifadəçilərin şəxsi məlumatlarının icazəsiz toplanması və ya yayılması.</li>
  <li>Sistemə texniki müdaxilə, avtomatlaşdırılmış məlumat toplama (scraping), təhlükəsizlik tədbirlərinin aşılması.</li>
</ul>

<h2 id="reytinq">9. Reytinq və məzmun</h2>
<p>Səfərdən sonra tərəflər bir-birini 1–5 ulduz aralığında qiymətləndirə və rəy yaza bilər. Rəylər real təcrübəyə əsaslanmalıdır. Təhqiramiz, yalan və ya reklam xarakterli rəylər xəbərdarlıq edilmədən silinir.</p>
<p>Platformaya yüklədiyin məzmuna (mətn, şəkil) görə məsuliyyət sənin üzərindədir və bu məzmunun Platformada göstərilməsi üçün bizə icazə vermiş olursan.</p>

<h2 id="mesuliyyet">10. Məsuliyyətin məhdudlaşdırılması</h2>
<p>Qanunvericiliyin icazə verdiyi maksimum həddə:</p>
<ul>
  <li>Platforma səfər zamanı baş verən <strong>yol-nəqliyyat hadisələrinə</strong>, gecikmələrə, ləğvlərə, itmiş əşyalara və istifadəçilər arasındakı mübahisələrə görə məsuliyyət daşımır.</li>
  <li>Platforma istifadəçilərin şəxsiyyətinə, sənədlərinin həqiqiliyinə və davranışına tam zəmanət vermir; yoxlama tədbirləri riski azaldır, lakin aradan qaldırmır.</li>
  <li>Xidmətin fasiləsiz və səhvsiz işləməsinə zəmanət verilmir; texniki işlər səbəbindən müvəqqəti dayanmalar ola bilər.</li>
</ul>
<p>Səfər zamanı sığorta təminatı sürücünün <strong>icbari nəqliyyat sığortası</strong> ilə tənzimlənir.</p>

<h2 id="dayandirma">11. Hesabın dayandırılması</h2>
<p>Bu qaydaların pozulması halında hesabı <strong>xəbərdarlıq edilmədən</strong> müvəqqəti dayandıra və ya birdəfəlik bloklaya bilərik. Bloklanmanın əsassız olduğunu düşünürsənsə, <a href="mailto:destek@yolyodasi.az">destek@yolyodasi.az</a> ünvanına müraciət edə bilərsən.</p>

<h2 id="mubahise">12. Mübahisələrin həlli</h2>
<p>Bu qaydalara <strong>Azərbaycan Respublikasının qanunvericiliyi</strong> tətbiq olunur. Mübahisələr ilk növbədə danışıqlar yolu ilə, razılıq əldə edilmədikdə isə Azərbaycan Respublikasının müvafiq məhkəmələrində həll edilir.</p>

<h2 id="deyisiklik2">13. Dəyişikliklər</h2>
<p>Qaydalara dəyişiklik edə bilərik. Yenilənmiş versiya bu səhifədə dərc olunduğu andan qüvvəyə minir. Əhəmiyyətli dəyişikliklər barədə tətbiqdaxili bildiriş göndərilir.</p>

<h2 id="elaqe2">14. Əlaqə</h2>
<p>Suallar üçün: <a href="mailto:info@yolyodasi.az">info@yolyodasi.az</a> və ya <a href="/contact">əlaqə səhifəsi</a>.</p>
BODY,
            ],

            'privacy' => [
                'title'              => 'Məxfilik Siyasəti',
                'lead'               => <<<'L'
Hansı məlumatları topladığımızı, nə üçün istifadə etdiyimizi və onları necə qoruduğumuzu sadə dildə izah edirik.
L,
                'content_updated_at' => '2026-09-13',
                'is_published'       => true,
                'body'               => <<<'BODY'
<h2 id="giris">1. Ümumi müddəalar</h2>
<p>Bu Məxfilik Siyasəti <strong>Yol Yoldaşı</strong> mobil tətbiqi və <strong>yolyodasi.az</strong> saytından (bundan sonra — “Platforma”) istifadə zamanı fərdi məlumatların hansı qaydada toplandığını, işləndiyini və qorunduğunu izah edir.</p>
<p>Platformadan istifadə etməklə sən bu sənəddə təsvir olunan şərtlərlə razılaşırsan. Razı deyilsənsə, Platformadan istifadə etməməyini xahiş edirik.</p>
<p>Fərdi məlumatların işlənməsi Azərbaycan Respublikasının <strong>“Fərdi məlumatlar haqqında”</strong> Qanununun tələblərinə uyğun həyata keçirilir.</p>

<h2 id="melumatlar">2. Topladığımız məlumatlar</h2>
<h3>2.1. Sənin birbaşa təqdim etdiyin məlumatlar</h3>
<ul>
  <li><strong>Hesab məlumatları:</strong> ad, soyad, telefon nömrəsi, profil şəkli (istəyə bağlı), əsas şəhər.</li>
  <li><strong>Sürücü məlumatları:</strong> avtomobilin markası, modeli, rəngi, buraxılış ili və dövlət qeydiyyat nişanı.</li>
  <li><strong>Sənədlər (yalnız sürücülər üçün):</strong> şəxsiyyət vəsiqəsi, sürücülük vəsiqəsi, avtomobilin qeydiyyat şəhadətnaməsi və icbari sığorta sənədi.</li>
  <li><strong>Səfər məlumatları:</strong> marşrut, tarix, saat, boş yer sayı, qiymət və əlavə qeydlər.</li>
  <li><strong>Ünsiyyət məzmunu:</strong> bron mesajları, rəylər, reytinqlər və dəstək xidmətinə müraciətlər.</li>
</ul>

<h3>2.2. Avtomatik toplanan məlumatlar</h3>
<ul>
  <li>Cihaz modeli, əməliyyat sistemi versiyası və tətbiq versiyası.</li>
  <li>IP ünvanı və təxmini region məlumatı.</li>
  <li>Tətbiqdaxili hərəkətlər (baxılan elanlar, edilən bronlar) — xidmətin təkmilləşdirilməsi üçün toplu (anonim) formada.</li>
  <li>Push bildirişlər üçün cihaz identifikatoru (FCM token).</li>
</ul>

<div class="callout">
  <p>Biz bank kartı və ya ödəniş rekvizitlərini <strong>toplamırıq və saxlamırıq</strong>. Səfər haqqı sürücü ilə sərnişin arasında birbaşa həll olunur.</p>
</div>

<h2 id="meqsed">3. İstifadə məqsədləri</h2>
<table>
  <tr><th>Məqsəd</th><th>İstifadə olunan məlumat</th></tr>
  <tr><td>Hesabın yaradılması və identifikasiya</td><td>Ad, telefon nömrəsi, SMS təsdiq kodu</td></tr>
  <tr><td>Elan və bron sisteminin işləməsi</td><td>Marşrut, tarix, yer sayı, qiymət, profil məlumatları</td></tr>
  <tr><td>Sürücülərin yoxlanılması və təhlükəsizlik</td><td>Sənəd şəkilləri, avtomobil məlumatları</td></tr>
  <tr><td>Bildirişlərin göndərilməsi</td><td>FCM token, bron statusu</td></tr>
  <tr><td>Sui-istifadə və fırıldaqçılığın qarşısının alınması</td><td>Cihaz məlumatları, IP, istifadə tarixçəsi</td></tr>
  <tr><td>Xidmətin təkmilləşdirilməsi</td><td>Anonimləşdirilmiş statistika</td></tr>
</table>

<h2 id="esas">4. Hüquqi əsas</h2>
<p>Məlumatları aşağıdakı əsaslarla işləyirik:</p>
<ul>
  <li><strong>Razılıq</strong> — qeydiyyatdan keçərkən və sənəd yükləyərkən verdiyin açıq razılıq.</li>
  <li><strong>Müqavilənin icrası</strong> — səninlə aramızdakı istifadə qaydalarının yerinə yetirilməsi.</li>
  <li><strong>Qanuni maraq</strong> — Platformanın təhlükəsizliyinin təmini və sui-istifadənin qarşısının alınması.</li>
  <li><strong>Qanunvericiliyin tələbi</strong> — səlahiyyətli dövlət orqanlarının qanuni sorğuları.</li>
</ul>

<h2 id="paylasim">5. Məlumatların paylaşılması</h2>
<p>Fərdi məlumatlarını <strong>satmırıq</strong>. Məlumatlar yalnız aşağıdakı hallarda paylaşılır:</p>
<ul>
  <li><strong>Digər istifadəçilərlə:</strong> ad, profil şəkli, reytinq, avtomobil məlumatları elan və axtarış nəticələrində görünür. <strong>Telefon nömrəsi yalnız bron təsdiqləndikdən sonra</strong> həmin səfərin iştirakçılarına açılır.</li>
  <li><strong>Xidmət təminatçıları ilə:</strong> Google Firebase (autentifikasiya, verilənlər bazası, fayl saxlama və push bildirişlər) və SMS operatoru. Bu təminatçılar məlumatları yalnız bizim tapşırığımızla işləyirlər.</li>
  <li><strong>Səlahiyyətli orqanlarla:</strong> qanunvericiliyin tələb etdiyi hallarda və qanuni sorğu əsasında.</li>
</ul>
<p>Sənədlərin (şəxsiyyət vəsiqəsi, sürücülük vəsiqəsi və s.) <strong>digər istifadəçilərə heç bir halda göstərilmir</strong> — onlara yalnız yoxlama üçün səlahiyyətli moderatorlar baxa bilər.</p>

<h2 id="mekan">6. Məkan məlumatları</h2>
<p>Xəritə və görüş nöqtəsi funksiyaları üçün cihazın məkanına müraciət edə bilərik. Bu icazə <strong>istəyə bağlıdır</strong> və cihaz tənzimləmələrindən istənilən vaxt söndürülə bilər. Məkan məlumatları fon rejimində daimi izlənilmir.</p>

<h2 id="bildiris">7. Bildirişlər</h2>
<p>Yeni bron, təsdiq, ləğv və səfər xatırlatmaları üçün push bildirişlər göndəririk. Bildirişləri tətbiqin <strong>Tənzimləmələr → Bildirişlər</strong> bölməsindən və ya cihaz tənzimləmələrindən söndürə bilərsən. Hesabla bağlı vacib texniki bildirişlər (məsələn, təhlükəsizlik xəbərdarlığı) davam edə bilər.</p>

<h2 id="muddet">8. Saxlanma müddəti</h2>
<ul>
  <li><strong>Hesab məlumatları</strong> — hesab aktiv olduğu müddətdə.</li>
  <li><strong>Səfər və bron tarixçəsi</strong> — mübahisələrin həlli üçün səfərdən sonra 12 ay.</li>
  <li><strong>Sürücü sənədləri</strong> — yoxlama tamamlandıqdan sonra qanunvericiliyin tələb etdiyi müddət ərzində, ən çoxu 24 ay.</li>
  <li><strong>Hesab silindikdə</strong> — məlumatlar 30 gün ərzində tam silinir və ya geri dönməz şəkildə anonimləşdirilir.</li>
</ul>

<h2 id="tehlukesizlik">9. Təhlükəsizlik</h2>
<p>Məlumatların qorunması üçün aşağıdakı tədbirləri görürük:</p>
<ul>
  <li>Bütün ötürmələr <strong>HTTPS/TLS</strong> ilə şifrələnir.</li>
  <li>Sənəd faylları məhdud girişli, şifrələnmiş anbarda saxlanılır.</li>
  <li>Verilənlər bazasına giriş rol əsaslı qaydalarla məhdudlaşdırılır.</li>
  <li>Autentifikasiya SMS birdəfəlik kodu (OTP) ilə aparılır — parol saxlanılmır.</li>
</ul>
<p>Bununla belə, internet üzərindən heç bir ötürmə 100% təhlükəsiz deyil. Hesabının təhlükəsizliyi üçün SMS kodunu heç kimlə paylaşma.</p>

<h2 id="huquqlar">10. Sənin hüquqların</h2>
<p>İstənilən vaxt aşağıdakı hüquqlardan istifadə edə bilərsən:</p>
<ul>
  <li>Haqqında saxlanılan məlumatlar barədə <strong>məlumat almaq</strong>;</li>
  <li>Yanlış məlumatların <strong>düzəldilməsini</strong> tələb etmək;</li>
  <li>Məlumatların <strong>silinməsini</strong> tələb etmək;</li>
  <li>Məlumatların işlənməsinə verdiyin <strong>razılığı geri götürmək</strong>;</li>
  <li>Məlumatlarının <strong>nüsxəsini</strong> əldə etmək.</li>
</ul>
<p>Müraciət üçün: <a href="mailto:mexfilik@yolyodasi.az">mexfilik@yolyodasi.az</a>. Sorğulara ən geci <strong>30 gün</strong> ərzində cavab veririk.</p>

<h2 id="silinme">11. Hesabın silinməsi</h2>
<p>Hesabı tətbiqdə <strong>Tənzimləmələr → Hesabı sil</strong> bölməsindən və ya <a href="/account-deletion">hesabın silinməsi səhifəsi</a> vasitəsilə silə bilərsən. Silinmə zamanı profil, elanlar, bronlar və sənədlər sistemdən çıxarılır; qanuni öhdəliklər səbəbindən saxlanması tələb olunan minimal qeydlər anonimləşdirilir.</p>

<h2 id="usaqlar">12. Yaş həddi</h2>
<p>Platforma <strong>18 yaşdan yuxarı</strong> şəxslər üçün nəzərdə tutulub. 18 yaşınadək şəxslərdən bilərəkdən məlumat toplamırıq. Belə bir hal aşkarlanarsa, məlumatlar dərhal silinir.</p>

<h2 id="kuki">13. Kukilər</h2>
<p>yolyodasi.az saytında yalnız saytın düzgün işləməsi üçün <strong>zəruri kukilərdən</strong> istifadə olunur (məsələn, qeydiyyat formasının vəziyyəti). Reklam və ya üçüncü tərəf izləmə kukiləri istifadə etmirik. Brauzer tənzimləmələrindən kuki qəbulunu məhdudlaşdıra bilərsən.</p>

<h2 id="deyisiklik">14. Dəyişikliklər</h2>
<p>Bu siyasətə vaxtaşırı dəyişiklik edə bilərik. Əhəmiyyətli dəyişikliklər tətbiqdaxili bildiriş və ya bu səhifədəki <strong>“Son yenilənmə”</strong> tarixi ilə elan olunur. Dəyişiklikdən sonra Platformadan istifadəni davam etdirmək yeni şərtlərin qəbulu deməkdir.</p>

<h2 id="elaqe">15. Əlaqə</h2>
<p>Məxfiliklə bağlı bütün suallar üçün:</p>
<ul>
  <li>E-poçt: <a href="mailto:mexfilik@yolyodasi.az">mexfilik@yolyodasi.az</a></li>
  <li>Dəstək: <a href="mailto:destek@yolyodasi.az">destek@yolyodasi.az</a></li>
  <li>Ünvan: Bakı şəhəri, Azərbaycan Respublikası</li>
</ul>
BODY,
            ],

            'about' => [
                'title'              => 'Haqqımızda',
                'lead'               => 'Yol Yoldaşı — Azərbaycanda şəhərlərarası səfərləri bölüşmək üçün qurulmuş platformadır.',
                'content_updated_at' => '2026-09-16',
                'is_published'       => true,
                'body'               => <<<'BODY'
<h2 id="biz-kimik">1. Biz kimik</h2>
<p><strong>Yol Yoldaşı</strong> şəhərlərarası səfərləri bölüşmək üçün qurulmuş Azərbaycan platformasıdır. Sürücü artıq getdiyi yolun boş oturacaqlarını elan edir, sərnişin isə həmin marşrutda yer bron edir.</p>
<p>Məqsədimiz sadədir: eyni istiqamətə gedən insanları bir avtomobildə birləşdirmək — yol xərcini azaltmaq, tıxacı və tullantını isə bir maşın qədər yüngülləşdirmək.</p>

<h2 id="nece-isleyir">2. Necə işləyir</h2>
<ul>
  <li><strong>Sürücü</strong> marşrutunu, tarixini, boş yer sayını və bir yerin qiymətini elan edir.</li>
  <li><strong>Sərnişin</strong> uyğun səfəri tapır və yer üçün müraciət göndərir.</li>
  <li>Sürücü müraciəti təsdiqləyəndən sonra tərəflər tətbiq daxilində yazışır və görüş nöqtəsini dəqiqləşdirir.</li>
  <li>Səfərdən sonra hər iki tərəf bir-birini qiymətləndirir — reytinq platformanın etibar əsasıdır.</li>
</ul>

<h2 id="tehlukesizlik">3. Təhlükəsizlik</h2>
<p>Təhlükəsizlik bizim üçün sonradan əlavə edilən funksiya deyil, platformanın təməlidir:</p>
<ul>
  <li>Hər hesab <strong>telefon nömrəsi ilə təsdiqlənir</strong> — anonim istifadəçi yoxdur.</li>
  <li>Sürücülər şəxsiyyət vəsiqəsi, sürücülük vəsiqəsi, avtomobil qeydiyyatı və sığorta sənədlərini yükləyir; sənədlər <strong>moderator tərəfindən əl ilə yoxlanılır</strong>.</li>
  <li>Sənədlər digər istifadəçilərə <strong>heç bir halda göstərilmir</strong>.</li>
  <li>Telefon nömrəsi yalnız bron təsdiqləndikdən sonra tərəflərə açılır.</li>
  <li>Qayda pozan hesablar barədə şikayət etmək olar — hər şikayətə baxılır.</li>
</ul>

<div class="callout">
  <p>Yol Yoldaşı <strong>taksi və ya sərnişindaşıma xidməti deyil</strong>. Platforma sürücü ilə sərnişini tanışdırır; səfər haqqı yalnız yol xərcinin bölüşdürülməsidir və kommersiya qazancı məqsədi daşımır.</p>
</div>

<h2 id="deyerlerimiz">4. Dəyərlərimiz</h2>
<table>
  <tr><th>Dəyər</th><th>Praktikada nə deməkdir</th></tr>
  <tr><td>Şəffaflıq</td><td>Qiymət elanda açıq göstərilir, gizli komissiya yoxdur.</td></tr>
  <tr><td>Etibar</td><td>Təsdiqlənmiş sənədlər və qarşılıqlı reytinq sistemi.</td></tr>
  <tr><td>Məxfilik</td><td>Şəxsi məlumat satılmır, nömrə vaxtından əvvəl açılmır.</td></tr>
  <tr><td>Əlçatanlıq</td><td>Qeydiyyat, elan və bron pulsuzdur.</td></tr>
</table>

<h2 id="komissiya">5. Komissiya siyasəti</h2>
<p>Hazırda platformadan istifadə <strong>tamamilə pulsuzdur</strong>. Qeydiyyat, elan yerləşdirmə və bron üçün heç bir haqq alınmır. Səfər haqqı sərnişindən birbaşa sürücüyə ödənilir və platforma bu ödənişə qarışmır.</p>

<h2 id="elaqe-about">6. Əlaqə</h2>
<p>Təklif, əməkdaşlıq və ya sualların üçün bizə yaza bilərsən — <a href="/contact">əlaqə səhifəsi</a> vasitəsilə müraciət et.</p>
BODY,
            ],
        ];
    }

    private function faqItems(): array
    {
        return [
            [
                'question' => <<<'Q'
Tətbiqdən istifadə pulludurmu?
Q,
                'answer'   => <<<'A'
Xeyr. Qeydiyyat, elan yerləşdirmə və bron tamamilə pulsuzdur. Səfər haqqını sərnişin birbaşa sürücüyə ödəyir, platforma komissiya götürmür.
A,
            ],
            [
                'question' => <<<'Q'
Sürücü kimi hansı sənədlər tələb olunur?
Q,
                'answer'   => <<<'A'
Şəxsiyyət vəsiqəsi, sürücülük vəsiqəsi, avtomobilin qeydiyyat şəhadətnaməsi və icbari sığorta. Sənədlər yalnız yoxlama üçündür və digər istifadəçilərə göstərilmir.
A,
            ],
            [
                'question' => <<<'Q'
Telefon nömrəm hamıya görünür?
Q,
                'answer'   => <<<'A'
Xeyr. Nömrə yalnız bron təsdiqləndikdən sonra həmin səfərin sürücüsü və sərnişini arasında qarşılıqlı açılır. Elan siyahısında nömrə görünmür.
A,
            ],
            [
                'question' => <<<'Q'
Bronu ləğv edə bilərəmmi?
Q,
                'answer'   => <<<'A'
Bəli, səfərə qədər istənilən vaxt. Qarşı tərəfə dərhal bildiriş gedir. Tez-tez ləğv edən hesabların reytinqi aşağı düşür və məhdudiyyət tətbiq oluna bilər.
A,
            ],
            [
                'question' => <<<'Q'
Ödəniş necə aparılır?
Q,
                'answer'   => <<<'A'
Hazırda ödəniş səfər zamanı sürücü ilə birbaşa, nağd və ya köçürmə ilə həyata keçirilir. Tətbiqdaxili ödəniş növbəti mərhələdə planlaşdırılır.
A,
            ],
            [
                'question' => <<<'Q'
Hesabımı necə silə bilərəm?
Q,
                'answer'   => <<<'A'
Tətbiqdə <strong>Tənzimləmələr → Hesabı sil</strong> bölməsindən və ya <a href="/account-deletion">hesabın silinməsi səhifəsindən</a> müraciət edə bilərsən. Məlumatlar 30 gün ərzində tam silinir.
A,
            ],
        ];
    }
}
