# Booked seat colours

The seat picker uses blue for male bookings and pink for female bookings. The
four legend items are Available, Selected, Male (booked), and Female (booked).
Available seats are white, selected seats green with a check mark, and occupied
seats have a small lock. Seat outlines and cushion details reinforce their colour.
Locked/unavailable seats and bookings with unknown gender also use blue, including
the outline, number, and lock icon. This is a visual fallback only: their actual
status and gender remain unchanged, and they cannot be selected. There is no
separate generic Booked legend item.

The confirmed mobile-backend contract adds optional `booked_gender` (`male`,
`female`, or `null`) to each seat returned by the authenticated
`GET /api/mobile/v1/schedules/{scheduleDetail}/seats` endpoint. Its `origin_id`,
`destination_id`, and `date` parameters, Bearer authentication, error formats,
and `status` values stay unchanged. The backend converts the overlapping ERP
ticket's gender (`1 = male`, `0 = female`) to these labels. Flutter does not infer
passenger gender from seat position or `gender_restriction`.

Deploy the matching `app/Services/Mobile/MobileTravelService.php` change from
`mobile-backend` to populate gender on live seat maps. No migration is needed.
Older API responses use the blue locked-seat fallback. Legacy `male`/`female` seat
statuses also retain gender colouring without changing their selectability.
