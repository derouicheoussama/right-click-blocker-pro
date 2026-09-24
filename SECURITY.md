# 🔒 Politique de sécurité

## Versions prises en charge

| Version | Prise en charge |
|---------|-----------------|
| 2.16.x  | ✅ |
| < 2.16  | ⚠️ mise à jour recommandée |

## Signaler une vulnérabilité

**Ne ouvrez JAMAUS d'issue public pour une faille de sécurité.**

Écrivez à **[hi@infinitycoder.dev](mailto:hi@infinitycoder.dev)** en français ou en anglais avec :
1. Une description du problème et son impact
2. Les étapes ou le proof-of-concept pour le reproduire
3. La version concernée et votre environnement

**Engagement :** accusé de réception sous 72 h, correctif sous 30 jours pour toute faille critique, crédit dans le changelog si vous le souhaitez.

## Mesures de protection intégrées

- **Licences ECDSA P-256** : les clés de licence sont signées numériquement — impossible de les forger côté client (la clé privée ne quitte jamais le site du vendeur).
- **Manifeste d'intégrité SHA-256** : chaque release embarque `.rcb-manifest.json` (hachage de chaque fichier) ; la page « À propos » du plugin détecte toute copie ou zip modifiés avant redistribution.
- **Sommes de contrôle** : chaque release publie `SHA256SUMS.txt` — vérifiez le zip téléchargé avant installation :
  ```bash
  sha256sum right-click-blocker-pro.zip        # Linux / macOS / Git Bash
  certutil -hashfile right-click-blocker-pro.zip SHA256   # Windows
  ```
- **Webhook Chargily signé HMAC-SHA256** : la livraison automatique des clés après paiement carte ne peut être déclenchée que par Chargily (signature vérifiée en temps constant).
- **Nonces + capacités** sur tous les formulaires et réponses AJAX, échappement en sortie, assainissement en entrée, garde `ABSPATH` sur chaque fichier PHP.
- **Aucune donnée sortante** : statistiques et journaux restent sur votre site ; aucun cookie, aucun traqueur.

## Vérifier l'intégrité après téléchargement

1. Téléchargez le zip depuis [Releases](https://github.com/derouicheoussama/right-click-blocker-pro/releases) (jamais depuis une source tierce).
2. Vérifiez le SHA-256 avec `SHA256SUMS.txt` de la même release.
3. Après activation, ouvrez **Infinity RCB Pro → À propos → 🔒 Intégrité des fichiers** : il doit afficher « Installation intègre ».

## Portée

Ce politique couvre le code de ce dépôt. Les sites hébergeant le plugin doivent appliquer leurs propres mesures (HTTPS, mots de passe forts, extensions à jour).
