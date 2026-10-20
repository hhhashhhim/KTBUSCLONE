#!/usr/bin/env python3
"""Build Flutter, Android and iOS branding from the supplied logo.

Run from any directory with Python 3 and Pillow:
    python3 tool/generate_branding.py
"""

import json
from pathlib import Path

from PIL import Image, ImageDraw, ImageOps


ROOT = Path(__file__).resolve().parents[1]
RES = ROOT / "android/app/src/main/res"
IOS = ROOT / "ios/Runner/Assets.xcassets"


def save(image, path):
    path.parent.mkdir(parents=True, exist_ok=True)
    image.save(path, optimize=True)


def centered(logo, size, width, background="white"):
    canvas = Image.new("RGBA", size, background)
    mark = ImageOps.contain(
        logo, (width, size[1]), method=Image.Resampling.LANCZOS
    )
    canvas.alpha_composite(
        mark, ((size[0] - mark.width) // 2, (size[1] - mark.height) // 2)
    )
    return canvas


def main():
    source = Image.open(ROOT / "tool/branding/logo.png").convert("RGBA")
    # Ignore nearly invisible export noise when locating the artwork. Preserve
    # all original pixels inside the crop, with four pixels of edge padding.
    bounds = source.getchannel("A").point(lambda a: 255 if a > 8 else 0).getbbox()
    if bounds is None:
        raise ValueError("The source logo contains no visible artwork.")
    left, top, right, bottom = bounds
    logo = source.crop((
        max(0, left - 4), max(0, top - 4),
        min(source.width, right + 4), min(source.height, bottom + 4),
    ))
    save(logo, ROOT / "assets/images/kainat-start-logo.png")

    # Legacy Android and iOS icons use 88% of the square's width.
    for density, scale in [("mdpi", 1), ("hdpi", 1.5), ("xhdpi", 2),
                           ("xxhdpi", 3), ("xxxhdpi", 4)]:
        folder = RES / f"mipmap-{density}"
        size = round(48 * scale)
        save(centered(logo, (size, size), round(size * .88)).convert("RGB"),
             folder / "ic_launcher.png")

        # The 58dp-wide logo (including text) fits inside Android's 66dp safe
        # circle on a 108dp adaptive foreground, so round masks do not clip it.
        size = round(108 * scale)
        save(centered(logo, (size, size), round(58 * scale), (0, 0, 0, 0)),
             folder / "ic_launcher_foreground.png")

        # Pre-Android 12 launch tile matches the larger Flutter logo panel.
        tile = Image.new("RGBA", (round(288 * scale), round(170 * scale)))
        ImageDraw.Draw(tile).rounded_rectangle(
            (0, 0, tile.width - 1, tile.height - 1),
            radius=round(28 * scale), fill="white",
        )
        tile.alpha_composite(centered(logo, tile.size, round(248 * scale),
                                      (0, 0, 0, 0)))
        save(tile, folder / "launch_image.png")

        # Android 12 uses a 288dp canvas with a 192dp visible circle.
        size = round(288 * scale)
        splash = Image.new("RGBA", (size, size))
        inset = round(49 * scale)
        ImageDraw.Draw(splash).ellipse(
            (inset, inset, size - inset - 1, size - inset - 1), fill="white"
        )
        splash.alpha_composite(centered(logo, splash.size, round(164 * scale),
                                        (0, 0, 0, 0)))
        save(splash, folder / "launch_image_android12.png")

    catalog = json.loads((IOS / "AppIcon.appiconset/Contents.json").read_text())
    for entry in catalog["images"]:
        size = round(float(entry["size"].split("x")[0]) *
                     float(entry["scale"].removesuffix("x")))
        save(centered(logo, (size, size), round(size * .88)).convert("RGB"),
             IOS / "AppIcon.appiconset" / entry["filename"])

    for scale, suffix in [(1, ""), (2, "@2x"), (3, "@3x")]:
        save(centered(logo, (288 * scale, 170 * scale), 248 * scale),
             IOS / "LaunchImage.imageset" / f"LaunchImage{suffix}.png")

    print("Generated Flutter logo, Android launcher/splash assets, and iOS assets.")


if __name__ == "__main__":
    main()
