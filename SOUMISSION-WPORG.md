# 📘 Guide de soumission WordPress.org — Infinity RCB Pro

> ⚠️ Ce fichier est un guide interne : **supprimez-le du zip avant l'envoi** à WordPress.org.

## 0. Avant de soumettre (checklist officielle wp.org — v2.9.2 déjà conforme)

- ✅ **Plugin Check (PCP)** : les URI `data:` qui déclenchaient une ERREUR ont été
  supprimées (icône de menu et logo de démo servies depuis des fichiers SVG
  embarqués : `assets/menu-icon.svg`, `assets/icon.svg`). Installez quand même
  le plugin officiel « Plugin Check » sur un site de test et lancez une analyse :
  tout ce qui resterait serait un faux positif (échappement de SVG statique
  généré par le plugin lui-même) à mentionner dans votre soumission.
- ✅ **Text domain** : retiré (l'interface est en français, aucune chaîne gettext) —
  plus aucun risque de mismatch avec le slug attribué.
- ✅ **Anti-trialware (règles 5 & 6)** : la licence optionnelle ne verrouille RIEN.
  Vérifié dans le code : la protection front ne référence jamais la licence.
  Des notes de conformité en anglais figurent en tête du fichier principal et
  de la classe licence (visibles du réviseur).
- ✅ **Nom (règle 17)** : « Right Click Blocker PRO – Right Click & Content
  Protection » — nom descriptif avec suffixe PRO distinctive, non identique
  à « Right Click Disable or Ban » ni « Right Click Ban » (awplife) ; à
  vérifier qu'aucun plugin homonyme n'existe au moment de la soumission.
  Compte wp.org à créer avec hi@infinitycoder.dev.
- ✅ **Règle 18 (différenciation)** : le readme affirme explicitement que DevTools,
  impression, stats, journaux et 10 styles sont GRATUITS là où les plugins
  comparables les verrouillent en Pro — c'est notre différenciation majeure.
- ✅ Pas de code arbitraire (le CSS personnalisé n'accepte que du CSS, balises
  PHP retirées), pas de téléchargement de code externe, aucune requête sortante.

## 1. Préparer le zip

> ⚠️ **« Invalid plugin slug » à l'upload ?** C'est le nom du DOSSIER dans le
> zip : jamais d'espaces ni de majuscules/accent (« Infinity RCB Pro » est
> refusé). Le dossier doit être `right-click-blocker-pro` (minuscules et
> tirets uniquement).

> ✅ **Zip prêt à soumettre** : `right-click-blocker-pro-2.16.0-wporg.zip`
> sur le Bureau (dossier slug valide, updater GitHub retiré, fichiers dev
> retirés, manifeste d'intégrité régénéré 32/32, version 2.16.0).
> Téléversez CE zip tel quel — plus rien à renommer.

Pour reconstruire ce zip (versions futures) : copier le plugin dans un
dossier `right-click-blocker-pro/`, retirer `.mimosa/`, `SOUMISSION-WPORG.md`,
`.zcodeignore` ET `includes/class-infinity-rcb-updater.php` (obligatoire :
Plugin Check bloque les routines de mise à jour personnalisées sur wp.org —
le plugin le charge seulement s'il est présent, la carte « Mises à jour »
repasse alors en mode wp.org seul sans erreur), régénérer
`.rcb-manifest.json` SANS l'updater (sinon l'intégrité signalerait le
fichier manquant), puis zipper le dossier (pas son contenu seul).

1. Supprimez ce fichier (`SOUMISSION-WPORG.md`) et le dossier `.mimosa/` s'il existe.
2. Vérifiez que le zip contient bien un unique dossier racine
   `right-click-blocker-pro/` avec `infinity-rcb-pro.php` et `readme.txt`
   à l'intérieur.

## 2. Soumettre le plugin

1. Créez un compte sur **wordpress.org** avec hi@infinitycoder.dev
   (https://login.wordpress.org/register) — le nom affiché peut être « Infinity Coder ».
2. Cochez les 3 étapes du formulaire (FAQ lues, nom distinctif, pas de trialware).
3. Allez sur **https://wordpress.org/plugins/developers/add/** et uploadez le zip.
4. Réponse sous **1 à 14 jours** par e-mail. Après approbation vous recevez un accès
   **SVN** (`https://plugins.trac.wordpress.org/browser/infinity-right-click-blocker`).

## 3. Publier via SVN (après approbation)

```
svn co https://plugins.svn.wordpress.org/infinity-right-click-blocker/ rcb
cd rcb
# Copier le plugin dans trunk/
cp -R "/chemin/infinity-right-click-blocker/." trunk/
# Assets de la fiche (bannières, icône, captures) dans assets/
mkdir -p assets
cp trunk/assets/icon-256.png trunk/assets/icon-128.png assets/
cp trunk/assets/banner-1544x500.png trunk/assets/banner-772x250.png assets/
cp trunk/assets/screenshot-*.png assets/
svn add --force trunk assets
svn ci -m "Initial release 2.9.2"
# Tagger la version
svn cp trunk tags/2.9.2
svn ci -m "Tag 2.9.2"
```

- `assets/` = icônes + bannières + **captures** (screenshot-1.png … screenshot-4.png,
  numérotées dans l'ordre de la section == Screenshots == du readme).
- Le numéro dans `Stable tag:` doit correspondre au tag créé.
- Toute mise à jour = modifier `trunk/`, incrémenter la version, re-tagger.

## 4. Points de conformité déjà vérifiés dans le code

- ✅ Licence GPL v2+ déclarée (header + readme).
- ✅ Aucune télémétrie externe, aucun appel tiers, aucun cookie.
- ✅ Aucune restriction de fonctionnalité (toutes les protections libres).
- ✅ Échappement + nonces sur tous les formulaires/AJAX ; écriture uniquement
  dans `wp-uploads` ; ABSPATH sur tous les fichiers PHP.
- ✅ **Mise à jour GitHub (v2.14.0) conforme au répertoire** : le canal
  GitHub ne s'active QUE si wp.org ne gère pas l'extension (absence des
  entrées `response`/`no_update`) — dès la publication sur wp.org (ou pour
  une installation depuis le répertoire), wp.org redevient la seule source
  de mise à jour. Aucune récupération du transiente d'update quand wp.org
  est maître.

## 8. Double canal de mise à jour (v2.14.0)

1. **WordPress.org** : automatique et prioritaire dès l'approbation —
   rien à faire.
2. **GitHub** (en attendant / installs manuelles) :
   - Réglages → Général → Mises à jour → dépôt `utilisateur/depot`
     (site vendeur) OU constante wp-config :
     `define( 'INFINITY_RCB_GITHUB_REPO', 'utilisateur/depot' );`
   - Créez une release : tag = numéro de version (ex. `2.14.1`), avec un
     **asset .zip** contenant le dossier `right-click-blocker-pro/` (zip du
     dossier, pas de son contenu seul).
   - Les sites vérifient toutes les 12 h (cache) ; bouton
     « Vérifier maintenant » pour forcer.

## 5. Positionnement face à la concurrence (ex. « Right Click Ban »)

| Argument | Nous (gratuit) | Eux (gratuit) |
|---|---|---|
| Détection DevTools | ✅ | ❌ (Pro) |
| Blocage impression / captures | ✅ | ❌ (Pro) |
| Statistiques temps réel + graphiques | ✅ | Simple compteur |
| Journaux + export CSV | ✅ | ❌ |
| Styles de messages | ✅ 10 + copyright auto | ✅ basique |
| Exclusions pages/admins | ✅ | ❌ (Pro) |
| Interface | FR, responsive, moderne | EN |
| Prérequis | WP 4.9+ / PHP 7.0+ | WP 5.0+ / PHP 7.4+ |

À mettre en avant dans les réponses du forum support et la description.

## 6. Site vendeur (votre vitrine commerciale)

Créez une page « Acheter » sur VOTRE site avec les trois shortcodes :

```
[rcb_demo]       ← démo interactive : 11 tests réels, journal en direct, 10 styles
[rcb_plans]      ← cartes des 3 licences (2 900 / 4 800 / 12 000 DA)
[rcb_commande]   ← formulaire de commande (e-mail automatique au vendeur)
```

Les commandes arrivent dans votre boîte mail ; validez-les depuis
Infinity RCB Pro → Licence & Achat → « 💰 Valider + clé » (la clé est
envoyée automatiquement à l'acheteur). Codes promo et clés révoquées :
`infinity_rcb_default_options()` → section `license` (`promo`, `revoked`).

## 7. Après la publication

- Répondre aux avis et au forum support (facteur clé de notation).
- Demander (poliment) un avis aux utilisateurs satisfaits.
- Publier des mises à jour régulières (changelog propre = confiance).
