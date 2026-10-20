# App branding

`tool/branding/logo.png` is the supplied original logo. The start screen uses
`assets/images/kainat-start-logo.png`, which removes transparent export padding
without changing the artwork. Its white panel is 288 logical pixels wide, with
20 pixels of padding, and shrinks to fit narrow screens. Other in-app logo
placements retain their existing asset.

Regenerate the Flutter logo and all native launcher/launch images with Python 3
and Pillow installed:

```sh
python3 tool/generate_branding.py
```

Legacy Android and iOS icons use 88% of the icon width. iOS icons are opaque RGB.
Android adaptive foregrounds keep the full logo and wording inside the
[66dp safe circle](https://developer.android.com/develop/ui/compose/system/icon_design_adaptive).
Android 12 has a separate launch image sized for its
[192dp visible circle](https://developer.android.com/develop/ui/views/launch/splash-screen#dimensions).
Earlier Android versions and iOS display the larger horizontal launch logo.

Native launcher icons and launch images require a full rebuild and installation;
Flutter hot reload alone does not update them. No API changes are required.
