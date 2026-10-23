# App branding

`tool/branding/logo.png` is the supplied original logo. The Flutter startup page
shows the app name, tagline, and update-check indicator on the green gradient,
without a logo panel. Other in-app logo placements use their existing assets.
The branding generator also produces `assets/images/kainat-start-logo.png` with
transparent export padding removed from the original artwork.

Startup has a quick entrance animation: on Android 12 and later, the native
launch logo gently scales up and fades away over 220 ms as Flutter draws its
first frame. The Flutter app name, tagline, and loading indicator fade in with a
small upward movement and zoom over 480 ms on Android and iOS. The motion runs
alongside startup checks and never adds a minimum waiting period. Flutter skips
the entrance motion when reduced motion is enabled; the native transition
follows Android's animation-duration setting. Update and maintenance gates keep
their existing routing.

Regenerate the Flutter logo and all native launcher/launch images with Python 3
and Pillow installed:

```sh
python3 tool/generate_branding.py
```

Legacy Android and iOS icons use 94% of the icon width. iOS icons are opaque RGB.
Android adaptive foregrounds use a 64dp-wide logo on a 108dp canvas, reducing
empty space while keeping the full logo and wording within the visible circular
launcher mask. Launcher sizing is separate from the in-app and splash artwork.
Android 12 has a separate launch image sized for its
[192dp visible circle](https://developer.android.com/develop/ui/views/launch/splash-screen#dimensions).
Earlier Android versions and iOS display the larger horizontal launch logo.

Native launcher icons and launch images require a full rebuild and installation;
Flutter hot reload alone does not update them. No API changes are required.
