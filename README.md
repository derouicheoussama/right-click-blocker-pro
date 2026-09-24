# 🛡️ Right Click Blocker PRO

**Protection de contenu professionnelle pour WordPress** — clic droit, copie, sélection, glisser-déposer, impression, captures d'écran et outils de développement, avec messages personnalisés, statistiques temps réel et journaux. **Tout est inclus, gratuitement.**

![Version](https://img.shields.io/badge/version-2.15.1-blue) ![WordPress](https://img.shields.io/badge/WordPress-4.9%20%E2%86%92%207.1-blue) ![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple) ![Licence](https://img.shields.io/badge/Licence-GPLv2%2B-green)

<p align="center"><img src="assets/banner-772x250.png" alt="Right Click Blocker PRO"></p>

## ✨ Fonctionnalités

| Protection | Incluse |
|---|---|
| 🖱️ Clic droit bloqué (menu contextuel) | ✅ |
| ⌨️ Raccourcis clavier (F12, Ctrl+U/S/P, Ctrl+Shift+I/J/C/K) | ✅ |
| 📋 Anti copier-coller (champs de saisie préservés) | ✅ |
| 🔤 Anti sélection de texte | ✅ |
| 🖐️ Anti glisser-déposer (images, liens) | ✅ |
| 🖨️ Anti impression (avertissement à la place du contenu) | ✅ |
| 📸 Anti capture d'écran (PrintScreen + presse-papiers vidé) | ✅ |
| 🛠️ Détection des outils de développement (flou / redirection) | ✅ |
| 💧 Filigrane d'images (copyright superposé) | ✅ |
| 🖼️ Anti-clickjacking (X-Frame-Options + frame busting) | ✅ |
| 📱 Protection tactile iOS/Android (long-press) | ✅ |

**Et aussi :**
- 🎨 **10 styles de messages** + couleurs personnalisées, copyright automatique, bouton de fermeture, barre de progression, bip sonore
- 👀 **Aperçu en temps réel** : formulaire à gauche, aperçu collant à droite qui rejoue le comportement réel (apparition → durée → disparition)
- 📊 **Tableau de bord temps réel** : KPI animés, graphiques 14/30 jours, répartition par type, IP uniques + widget tableau de bord WordPress
- 📜 **Journaux détaillés** filtrables et exportables CSV, rétention automatique
- 🚨 **Alerte e-mail** en cas de pic de tentatives (seuil par IP/heure)
- 📧 **E-mails HTML professionnels** (multipart, anti-spam) pour tout le parcours de commande
- 🛒 **Boutique publique** : shortcodes `[rcb_plans]`, `[rcb_commande]`, `[rcb_demo]` + bloc Gutenberg « Démo de protection »
- 💳 **Paiement carte CIB / Edahabia** (Chargily Pay, site vendeur) et WhatsApp — livraison de la clé 100 % automatique
- 🔑 **Licences à vie signées ECDSA** (Mono-Site 2 900 DA · 5 Sites 4 800 DA · Agence 20 domaines 12 000 DA) — optionnelles, aucune fonctionnalité verrouillée
- 💾 Import/export JSON des réglages, exclusions pages/admin/rôles, multisite

## 📥 Installation

### Depuis WordPress.org (recommandé)
Extensions → Ajouter → « Right Click Blocker PRO » → Installer → Activer.

### Depuis GitHub (installation manuelle)
1. Téléchargez le `.zip` de la [dernière release](https://github.com/derouicheoussama/right-click-blocker-pro/releases/latest)
2. Extensions → Ajouter → Téléverser → le `.zip` → Activer
3. Les mises à jour arriveront **automatiquement depuis GitHub** (voir ci-dessous)

## 🔄 Mises à jour (double canal, sans conflit)

- **WordPress.org** : automatique et prioritaire dès que l'extension y est publiée (ou installée depuis le répertoire).
- **GitHub** : pour les installations manuelles — le plugin consulte les releases de ce dépôt (toutes les 12 h, ou bouton **« Vérifier maintenant »** dans Réglages → Général → Mises à jour). Ce canal **se désactive tout seul** dès que wp.org gère l'extension.
- Dépôt personnalisé : `define( 'INFINITY_RCB_GITHUB_REPO', 'utilisateur/depot' );` dans wp-config.php.

### Publier une nouvelle version (pour le développeur)

```bash
# 1. Mettre à jour la version dans infinity-rcb-pro.php + readme.txt (Stable tag)
# 2. Commiter, puis créer le tag :
git tag v2.15.2 && git push origin v2.15.2
# 3. C'est tout : GitHub Actions construit right-click-blocker-pro.zip
#    (sans README.md, .github/, fichiers de dev) et publie la release.
```

Les sites installés depuis GitHub proposeront la mise à jour en 1 clic dans **Extensions**, avec les notes de release dans la fiche détails.

## 🧭 Utilisation rapide

1. **Tableau de bord** : Infinity RCB Pro → état global, aperçu du message, activité en direct
2. **Réglages → Apparence** : personnalisez à gauche, voyez à droite en temps réel
3. **Réglages → Avancé** : filigrane, anti-clickjacking, exclusions (pages, rôles), seuil d'alerte
4. **Licence & Achat** : parcours guidé — commander → payer → « J'ai payé » → clé automatique par e-mail
5. **Boutique** (site vendeur) : `[rcb_plans]` `[rcb_commande]` `[rcb_demo]` sur vos pages de vente

## 📸 Captures

| Tableau de bord | Personnalisation temps réel | Message visiteur |
|---|---|---|
| ![](assets/screenshot-1.png) | ![](assets/screenshot-3.png) | ![](assets/screenshot-4.png) |

## 🔒 Sécurité & protection du code

Même si quelqu'un télécharge le code ou redistribue un zip modifié :

- **Licences infalsifiables** : les clés de licence sont signées **ECDSA P-256** — impossible d'en générer hors du site du développeur (la clé privée ne quitte jamais le serveur du vendeur ; le plugin n'embarque que la clé publique).
- **Manifeste d'intégrité SHA-256** : chaque release embarque `rcb-manifest.json` (hachage de chaque fichier). Après activation, **Infinity RCB Pro → À propos → 🔒 Intégrité des fichiers** affiche « Installation intègre » ou **la liste des fichiers modifiés** d'une copie altérée.
- **Zip vérifiable** : chaque release joint `SHA256SUMS.txt` :
  ```bash
  sha256sum right-click-blocker-pro.zip                 # Linux / macOS / Git Bash
  certutil -hashfile right-click-blocker-pro.zip SHA256 # Windows
  ```
- **Webhook paiement signé** (HMAC-SHA256, comparaison en temps constant) : seul Chargily peut déclencher la livraison d'une clé.
- **Nonces, capacités, échappement, ABSPATH** sur tous les points d'entrée ; aucune donnée sortante, aucun cookie.

Signalement de vulnérabilité : voir [SECURITY.md](SECURITY.md) — divulgation responsable par e-mail, jamais en issue public.

## ⚖️ Licence

GPL v2 ou ultérieure — voir [LICENSE.md](LICENSE.md). Développé avec ❤️ par **Infinity Coder** — [hi@infinitycoder.dev](mailto:hi@infinitycoder.dev).

> Licence de support à vie optionnelle (à partir de 2 900 DA ≈ 11,90 €) : elle finance le développement et offre un support prioritaire — **toutes les fonctionnalités restent gratuites et complètes sans licence**.
