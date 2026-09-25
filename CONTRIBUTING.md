# Contribuer à Right Click Blocker PRO 🛡️

Merci de votre intérêt ! Ce document explique comment signaler un problème ou proposer une amélioration.

## 🐭 Signaler un bug

1. Vérifiez que le bug n'est pas déjà signalé dans les [issues](https://github.com/derouicheoussama/right-click-blocker-pro/issues)
2. Ouvrez une nouvelle issue avec le modèle **« Signalement de bug »**
3. Incluez impérativement :
   - Version du plugin (À propos → version)
   - Version de WordPress et de PHP
   - Thème actif + autres extensions (si pertinent)
   - Les étapes pour reproduire, et le comportement attendu
   - Les messages d'erreur (captures bienvenues)

## 💡 Proposer une amélioration

Ouvrez une issue avec le modèle **« Proposition d'amélioration »** : décrivez le besoin avant la solution — les propositions alignées sur la philosophie du plugin (gratuit, léger, sans donnée sortante, conforme au répertoire WordPress.org) seront prioritaires.

## 🔧 Soumettre du code

```bash
# 1. Forkez puis créez une branche descriptive
git checkout -b feature/nom-court

# 2. Développez en respectant les règles ci-dessous

# 3. Vérifiez la syntaxe de chaque fichier modifié
php -l includes/class-infinity-rcb.php

# 4. Commitez avec un message clair (français ou anglais accepté)
git commit -m "Ajoute …"

# 5. Poussez et ouvrez une Pull Request vers main
```

### Standards du projet

- **PHP** : [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) — préfixes `infinity_rcb_` / `Infinity_RCB_`, échappement systématique (`esc_html`, `esc_attr`, `esc_url`), nonces + capacités sur toute action
- **JS** : vanilla, sans jQuery ; indentation par tabulations
- **CSS** : préfixes de classes `.rcb-` ; cohérence avec les variables existantes
- **Compatibilité minimale** : WordPress 4.9, PHP 7.0
- **Conformité wp.org** : aucune fonctionnalité verrouillée derrière une licence, aucun appel réseau sortant par défaut, pas de fichier point dans la distribution
- **Sécurité** : toute entrée utilisateur est assainie ; toute sortie est échappée — voir [SECURITY.md](SECURITY.md) pour la divulgation responsable des vulnérabilités (jamais en issue public)

### Ce qui sera refusé

- Télémétrie ou tracking sortant
- Fonctionnalités payantes verrouillantes (guides 5-6 du répertoire wp.org)
- Bibliothèques lourdes là où du natif suffit

## 🏷️ Versions

Versionnement **sémantique** (`MAJEUR.MINEUR.CORRECTIF`). Les releases sont construites automatiquement par GitHub Actions à chaque tag `vX.Y.Z` — voir le [README](README.md#publier-une-version-développeur).
