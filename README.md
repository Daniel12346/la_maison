# La Maison

Ovaj projekt predstavlja jednostavan rezervacijski sustav za restoran imena "La Maison". Sustav se sastoji od stranice na kojoj korisnik obavlja svoju rezervaciju i administratorskog dijela gdje se sve rezervacije mogu pregledati. Administratorski dio nudi i detaljan pregled svake zasebne rezervacije gdje se može mijenjati njeno stanje. Za izradu projekta korišten je PHP s frameworkom Symfony. Baza podataka je PostgreSQL, a
frontend se temelji na Twig predlošcima. Za testove je korišten PHPunit.

## ⚙️ Instalacija projekta `la_maison` na Windowsu

### 🔧 Preduvjeti (potrebno instalirati ručno)

Prije svega, instaliraj sljedeće alate:

- **[Git](https://git-scm.com/download/win)** — za kloniranje repozitorija
- **[PHP 8.2+](https://windows.php.net/download/)** — zahtijeva se verzija `>=8.2`
- **[Composer](https://getcomposer.org/download/)** — upravitelj PHP paketa
- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)** — za pokretanje PostgreSQL baze podataka

---

### 📥 1. Kloniranje repozitorija

```bash
git clone https://github.com/Daniel12346/la_maison.git
cd la_maison
```

> Preuzima projekt s GitHuba i ulazi u mapu projekta.

---

### 📦 2. Instalacija PHP ovisnosti

```bash
composer install
```

> Instalira sve pakete navedene u `composer.json` (Symfony, Doctrine, Twig, itd.). Također automatski čisti cache, instalira assete i pokreće `importmap:install`.

---

### 🔑 3. Postavljanje varijabli okoline

Kopiraj `.env` datoteku u lokalnu verziju:

```bash
copy .env .env.local
```

> Kreira lokalnu konfiguracijsku datoteku koja nije praćena Gitom. Otvori `.env.local` u uređivaču teksta i postavi:

```dotenv
APP_SECRET=nekiRandomniString32ZnakaDugacak
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

> `APP_SECRET` može biti bilo koji nasumičan niz od 32 znaka. `DATABASE_URL` mora odgovarati postavkama baze (korisnik, lozinka, naziv baze).

---

### 🐳 4. Pokretanje baze podataka (Docker)

```bash
docker compose up -d
```

> Pokreće PostgreSQL kontejner u pozadini, kako je definirano u `compose.yaml`. Baza podataka bit će dostupna na `127.0.0.1:5432`.

---

### 🗄️ 5. Kreiranje baze podataka

```bash
php bin/console doctrine:database:create
```

> Kreira praznu bazu podataka u PostgreSQL-u.

---

### 🏗️ 6. Pokretanje migracija

```bash
php bin/console doctrine:migrations:migrate
```

> Izvršava sve migracije i kreira tablice u bazi podataka. Potvrdi unosom `yes` kada se to zatraži.

---

### 🌱 7. (Opcionalno) Učitavanje testnih podataka

```bash
php bin/console doctrine:fixtures:load
```

> Puni bazu s primjerima rezervacija (20 unosa) koji su definirani u `src/DataFixtures/ReservationFixture.php`. Potvrdi unosom `yes`.

---

### 🚀 8. Pokretanje razvojnog servera

```bash
php -S localhost:8000 -t public
```

> Pokreće ugrađeni PHP server. Aplikacija je dostupna na [http://localhost:8000](http://localhost:8000).

> **Alternativno**, ako je instaliran [Symfony CLI](https://symfony.com/download):
>
> ```bash
> symfony server:start
> ```

---

### 🧪 9. (Opcionalno) Pokretanje testova

```bash
php bin/phpunit
```

> Pokreće sve PHPUnit testove. Testovi koriste zasebnu konfiguraciju iz `.env.test` i ne trebaju aktivnu bazu podataka (koriste mockove).

---

### 📋 Sažetak svih naredbi

```bash
git clone https://github.com/Daniel12346/la_maison.git
cd la_maison
composer install
copy .env .env.local
docker compose up -d
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php -S localhost:8000 -t public
```

## Javna stranica

![Početna stranica rezervacije](public/assets/image.png)

Početna stranica preko koje korisnik obavlja rezervaciju je na uobičajenoj početnoj ruti "/" (ako je projekt lokalno pokrenut na https://localhost:8000, ta ista lokacija predstavlja početnu stranicu).  
Dizajn početne stranice je jednostavan s crno-zlatnom temom, kombinacijom boja koja predstavlja luksuz. Na sredini početne stranice je postavljen form za rezervaciju, s namjerom kako bi se korisniku privukla pozornost na njega. Na početku su u formu vidljiva samo dva polja, za broj gostiju u rezervaciji i željeni datum. Ako je odabrani datum petak ili nedjelja, vidljiva je i opcija za privatne rezervacije s kratkim pojašnjenjem te opcije. Sa samo 2, odnosno 3 polja, form u trenutku kad ga korisnik prvi put vidi izgleda jednostavnije i pristupačnije nego da odmah vidi sva ostala polja koja u tom trenutku nisu zapravo potrebna, a njihova prisutnost instinktivno može asocirati korisnik na potreban dodatan trud i vrijeme i tako ga odbiti. Ako u bilo kojem trenutku korisnik unese vrijednost koja nije dozvoljena za broj gostiju ili datum (datum mora biti najviše 30 dana nakon sadašnjeg), ispod odgovarajućeg polja prikazuje se upozorenje i pojašnjenje greške.  
Kad korisnik ispuni ova osnovna polja, prikazuju mu se dostupni vremenski termini za odabrani broj gostiju i datum. Termini koji su zauzeti nisu prikazani. Dostupni termini se automatski ažuriraju kad korisnik promijeni broj gostiju ili datum. Tek nakon odabira termina, kad je jasno da korisnik može obaviti željenu rezervaciju, prikazuju se ostala polja za e-mail, ime korisnika, broj telefona i dodatne zahtjeve. Po podnošenju forma, sva polja se provjeravaju. Ako form nije valjan, korisniku se ističe razlog na odgovarajućem mjestu. Ako je form valjan, stvara se referentni kod rezervacije i korisnika se preusmjerava na stranicu s potvrdom rezervacije ("/confirmation") gdje vidi podatke rezervacije, uključujući i referentni kod. Status uspješno stvorene rezervacije je "Pending" dok ga se ne promijeni u administratorskom dijelu.

## Administratorski dio

![Uređivanje rezervacije (admin)](public/assets/reservation_edit.png)

Pretpostavka je da administrator ne mora biti tehnološki stručnjak pa sučelje mora biti jednostavno i jasno. Pregled svih rezervacija je na ruti "/admin/reservation".  
Rezervacije se mogu sortirati prema bilo kojem polju i filtrirati po datumu i stanju. Ako su filtrirane po datumu, za taj datum je označen i ukupan broj gostiju.
Iako to nije navedeno u opisu zadatka, u ovom pregledu administratoru je prikazano i je li rezervacija privatna ili ne, iz razloga što privatna rezervacija i "obična" mogu postojati u istom termin pa je moguće da za isti termin ima još kapaciteta za nove obične ali ne i privatne ili obratno. Bez informacije da je jedna od njih privatna, administrator ne bi mogao razumjeti ovu situaciju.  
Kako bi se administratoru privukla pažnja na neke njima posebno bitne aspekte rezervacija, oni su istaknuti jednostavnim ikonama i bojama: vremenski termin koji je popunjen označen je lokotom na rezervacijama s takvim terminom, narančastom točkom su označene rezervacije s posebnim korisničkim zahtjevima, a različita stanja rezervacije su posebno istaknuta prikladnim bojama jer njih administrator može mijenjati u pregledu pojedinačne rezervacije na ruti "/admin/reservation/{id}/edit", gdje je id jedinstveni identifikator rezervacije. Ovoj ruti se pristupa klikom na rezervaciju u pregledu svih rezervacija. Na toj ruti je također dodatno istaknuto da se tu upravlja statusom rezervacije (ostali podatci postojeće rezervacije ne mogu se mijenjati). Rezervacije sa statusom "Cancelled" se ne računaju kod provjeravanja popunjenosti termina. Uz potvrđene ("Confirmed"), računaju se i one koje još nisu potvrđene ("Pending") jer se pretpostavlja da će biti potvrđene pa je bolje odmah osigurati kapacitet za njih.

## Testiranje

Odmah želim istaknuti da su svi testovi implementirani pomoću umjetne inteligencije. Ja sam odlučivao što testirati, umjetna inteligencija (većinom model gpt5-mini unutar Github Copilota) je pisala testove. Trenutno nemam dovoljno iskustva s pisanjem testova da ih samostalno radim, ali razumijem vrijednost testiranja i rado bih učio više o testiranju, pogotovo za stvarne projekte. Ako koji test ne radi ono što bi trebao, odgovornost je moja. Testovi ne koriste stvarnu bazu podataka nego njezin mock. Većina testova se odnosi na validaciju, pogotovo za ona polja za koja je validacija u entitetu za rezervacije složenija.
Projekt sadrži 5 testnih klasa (plus bootstrap datoteku) koje pokrivaju kontrolere, validaciju i lifecycle ponašanje entiteta, logiku repozitorija te prilagođena pravila kapaciteta.

### Testovi kontrolera

- ReservationControllerTest.php  
  Provjerava ponašanje slanja rezervacije i prikaza potvrde:
    - valjano slanje forme preusmjerava na stranicu potvrde
    - nevaljano slanje ponovno prikazuje početnu stranicu s greškama bez spremanja (HTTP 422)
    - stranica potvrde ispravno prikazuje podatke rezervacije

- ReservationFlowTest.php  
  Simulira neispravan end-to-end tok slanja forme (uključujući CSRF) i potvrđuje da aplikacija sigurno vraća korisnika na početnu stranicu bez upisa u bazu (renderanje početne stranice s kodom 422).

- ReservationFormLiveComponentTest.php  
  Pokriva dinamičko ponašanje forme: nakon odabira broja gostiju i datuma, dostupni termini se učitavaju i prikazuju u formi.

### Testovi entiteta

- ReservationTest.php  
  Validira osnovna pravila entiteta Reservation:
    - odbija datum izvan dozvoljenog raspona od 30 dana
    - generira referentni kod u očekivanom formatu kroz lifecycle callback

### Testovi repozitorija

- ReservationRepositoryTest.php  
  Testira logiku dostupnih termina:
    - popunjeni termini se uklanjaju iz liste dostupnih
    - otkazane rezervacije ne blokiraju termin (za regularne i privatne kontekste)

### Testovi validatora

- MaxCapacityValidatorTest.php  
  Testira prilagođena pravila maksimalnog kapaciteta:
    - regularne rezervacije ignoriraju broj gostiju privatnih rezervacija za zajednički limit od 20 mjesta
    - privatna rezervacija je valjana ako ne postoji druga privatna u istom terminu
    - dvije privatne rezervacije u istom terminu nisu dozvoljene
    - pojavljuje se greška kada regularni kapacitet prelazi 20

### Bootstrap za testove

- bootstrap.php  
  PHPUnit bootstrap datoteka za inicijalizaciju testnog okruženja.

## Pokretanje testova

Pokreni sve testove:

```bash
php .\bin\phpunit
```

Pokreni samo jedan testni skup:

```bash
php .\bin\phpunit tests\Validator\MaxCapacityValidatorTest.php
```
