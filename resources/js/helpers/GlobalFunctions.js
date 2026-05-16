import { event } from "jquery";
import moment from "moment";

const DEPARTURE_DATE_TIME_FORMATS = [
    "YYYY-MM-DD HH:mm:ss",
    "YYYY-MM-DD HH:mm",
    "YYYY-MM-DD hh:mm:ss A",
    "YYYY-MM-DD hh:mm A",
    "YYYY-MM-DD h:mm:ss A",
    "YYYY-MM-DD h:mm A",
    "YYYY/MM/DD HH:mm:ss",
    "YYYY/MM/DD HH:mm",
    "YYYY/MM/DD hh:mm:ss A",
    "YYYY/MM/DD hh:mm A",
    "DD-MM-YYYY HH:mm:ss",
    "DD-MM-YYYY HH:mm",
    "DD-MM-YYYY hh:mm:ss A",
    "DD-MM-YYYY hh:mm A",
    "DD/MM/YYYY HH:mm:ss",
    "DD/MM/YYYY HH:mm",
    "DD/MM/YYYY hh:mm:ss A",
    "DD/MM/YYYY hh:mm A",
    "MMM D YYYY HH:mm:ss",
    "MMM D YYYY HH:mm",
    "MMM D YYYY hh:mm:ss A",
    "MMM D YYYY hh:mm A",
    "D MMM YYYY HH:mm:ss",
    "D MMM YYYY HH:mm",
    "D MMM YYYY hh:mm:ss A",
    "D MMM YYYY hh:mm A",
];

const DEPARTURE_TIME_ONLY_FORMATS = [
    "HH:mm:ss",
    "HH:mm",
    "hh:mm:ss A",
    "hh:mm A",
    "h:mm:ss A",
    "h:mm A",
];

const DEFAULT_DEPARTURE_DATE_KEYS = [
    "departure_date",
    "schedule_date",
    "date",
    "new_departure_date",
    "old_departure_date",
];

const DEFAULT_DEPARTURE_TIME_KEYS = [
    "departure_time",
    "departure_city_time",
    "time",
];

const DEFAULT_DEPARTURE_COMBINED_KEYS = [
    "departure_datetime",
    "departureDateTime",
    "schedule_datetime",
    "scheduleDateTime",
    "departure_at",
    "bus_time",
    "new_bus_time",
    "old_bus_time",
];

const normalizeDateTimeCandidate = (candidate) => {
    if (candidate === undefined || candidate === null || candidate === "") {
        return "";
    }

    if (moment.isMoment(candidate)) {
        return candidate.clone();
    }

    if (candidate instanceof Date || typeof candidate === "number") {
        return moment(candidate);
    }

    const value = String(candidate).trim().replace(/\s+/g, " ");

    if (!value) {
        return "";
    }

    if (value.includes(" - ")) {
        return value.split(" - ")[0].trim();
    }

    return value;
};

const parseDateTimeCandidate = (candidate) => {
    const normalized = normalizeDateTimeCandidate(candidate);

    if (!normalized) {
        return null;
    }

    if (moment.isMoment(normalized)) {
        return normalized.isValid() ? normalized : null;
    }

    if (normalized instanceof Date) {
        const parsedFromDate = moment(normalized);
        return parsedFromDate.isValid() ? parsedFromDate : null;
    }

    const strictIso = moment(normalized, moment.ISO_8601, true);
    if (strictIso.isValid()) {
        return strictIso;
    }

    const strictDateTime = moment(normalized, DEPARTURE_DATE_TIME_FORMATS, true);
    if (strictDateTime.isValid()) {
        return strictDateTime;
    }

    const looseDateTime = moment(normalized);
    if (looseDateTime.isValid()) {
        return looseDateTime;
    }

    return null;
};

const parseDepartureMoment = (
    payload,
    {
        dateKeys = [],
        timeKeys = [],
        combinedKeys = [],
        fallbackDate = null,
        fallbackTime = null,
    } = {}
) => {
    if (!payload && !fallbackDate && !fallbackTime) {
        return null;
    }

    if (
        moment.isMoment(payload) ||
        payload instanceof Date ||
        typeof payload === "string" ||
        typeof payload === "number"
    ) {
        const directValue = parseDateTimeCandidate(payload);
        if (directValue) {
            return directValue;
        }
    }

    const row = payload && typeof payload === "object" ? payload : {};
    const mergedDateKeys = [...dateKeys, ...DEFAULT_DEPARTURE_DATE_KEYS];
    const mergedTimeKeys = [...timeKeys, ...DEFAULT_DEPARTURE_TIME_KEYS];
    const mergedCombinedKeys = [...combinedKeys, ...DEFAULT_DEPARTURE_COMBINED_KEYS];

    const departureDate = mergedDateKeys
        .map((key) => row?.[key])
        .find((value) => value !== undefined && value !== null && value !== "") ?? fallbackDate;

    for (const key of mergedCombinedKeys) {
        const combinedValue = row?.[key];

        if (combinedValue === undefined || combinedValue === null || combinedValue === "") {
            continue;
        }

        const parsedCombined = parseDateTimeCandidate(combinedValue);
        if (parsedCombined) {
            return parsedCombined;
        }

        if (departureDate) {
            const timeOnly = normalizeDateTimeCandidate(combinedValue);
            const parsedWithDate = moment(
                `${departureDate} ${timeOnly}`,
                [...DEPARTURE_DATE_TIME_FORMATS, ...DEPARTURE_TIME_ONLY_FORMATS],
                true
            );

            if (parsedWithDate.isValid()) {
                return parsedWithDate;
            }
        }
    }

    const departureTime = mergedTimeKeys
        .map((key) => row?.[key])
        .find((value) => value !== undefined && value !== null && value !== "") ?? fallbackTime;

    if (departureDate && departureTime) {
        const combinedDateTime = moment(
            `${departureDate} ${departureTime}`,
            [...DEPARTURE_DATE_TIME_FORMATS, ...DEPARTURE_TIME_ONLY_FORMATS],
            true
        );

        if (combinedDateTime.isValid()) {
            return combinedDateTime;
        }
    }

    if (departureTime) {
        const parsedTimeOnly = parseDateTimeCandidate(departureTime);
        if (parsedTimeOnly) {
            return parsedTimeOnly;
        }
    }

    return null;
};

const formatRemainingTime = (remainingMinutes) => {
    if (remainingMinutes < 1) {
        return "Less than a minute remaining";
    }

    const hours = Math.floor(remainingMinutes / 60);
    const minutes = remainingMinutes % 60;

    if (!hours) {
        return `${minutes} minute${minutes === 1 ? "" : "s"} remaining`;
    }

    if (!minutes) {
        return `${hours} hour${hours === 1 ? "" : "s"} remaining`;
    }

    return `${hours} hour${hours === 1 ? "" : "s"} ${minutes} minute${minutes === 1 ? "" : "s"} remaining`;
};

const buildDepartureStatusMeta = (departureMoment) => {
    if (!departureMoment || !departureMoment.isValid()) {
        return {
            statusKey: "unknown",
            toneClass: "",
            statusLabel: "Departure time unavailable",
            remainingMinutes: null,
            remainingText: "",
            departureMoment: null,
            departureText: "",
            isPast: false,
            isValid: false,
        };
    }

    const now = moment();
    const remainingMinutes = Math.ceil(departureMoment.diff(now, "minutes", true));
    const isPast = now.isAfter(departureMoment);

    let statusKey = "";
    let statusLabel = "More than 6 hours remaining";

    if (isPast) {
        statusKey = "red";
        statusLabel = "Departed";
    } else if (remainingMinutes <= 30) {
        statusKey = "yellow";
        statusLabel = "30 minutes or less remaining";
    } else if (remainingMinutes <= 120) {
        statusKey = "green";
        statusLabel = "2 hours or less remaining";
    } else if (remainingMinutes <= 360) {
        statusKey = "white";
        statusLabel = "6 hours or less remaining";
    }

    return {
        statusKey,
        toneClass: statusKey ? `departure-status--${statusKey}` : "",
        statusLabel,
        remainingMinutes: Math.max(remainingMinutes, 0),
        remainingText: isPast ? "Departure time has passed" : formatRemainingTime(Math.max(remainingMinutes, 0)),
        departureMoment,
        departureText: departureMoment.format("DD-MMM-YYYY hh:mm A"),
        isPast,
        isValid: true,
    };
};

/*
    this function will work on input text field
    all parameter are optional except first one that is event
    by default it validate only number will be accepted
    dot = true/false | accept/not accept
    start = start range of number
    end = end range of number
    negative = true/false | accept/not accept
    len = mean you can't exceed that number
*/
export default {
    numberValidate(event, { dot = false, maxLen = null, negative = false, comma = false } = {}) {
        
        const charCode = event.charCode;
        const value = event.target.value.toString().replace(/,/g, '');
        
        // Allow numbers (48-57), dot (46), and control keys (0)
        if ((charCode >= 48 && charCode <= 57) || charCode === 0) {
    
            // Check the length if it's not null
            if (maxLen !== null && value.length >= maxLen) {
                event.preventDefault();
                return false;
            }

            return true;
        }
        // Accept dot
        if (dot && charCode === 46) {
            // Allow only one dot
            if (value.includes('.')) {
                event.preventDefault();
                return false;
            }
    
            // Check the length if it's not null
            if (maxLen !== null && value.length >= maxLen) {
                event.preventDefault();
                return false;
            }
            return true;
        }
        // Accept negative value
        if (negative && charCode === 45) {
            if (value.includes('-') || value.length !== 0) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    
        event.preventDefault();
        return false;
    },
    
    /*
        this function will get a simple array without key and return true if any value will repeat in the array
    */
    indicateDuplication(data) {
        const values = data.filter((item, index) => data.indexOf(item) !== index)
    
        if(values.length > 0)
        {
            return true;
        }
        return false
        
    },
    
    testGlobal(event) {
        alert('helo'); 
    },
    
    /*
        to apply comma on keyup
    */
    removeComma(value) 
    {
        return value.toString().replace(/,/g, '');
    },  
    /*
        this function get amount and converted the amount to comma seprater,
        second peramet will be optional | by default it converted into this type of format (00,00,000)
    */
    insertComma(value) {
    if (!value) return '0';

    // Remove existing commas and decimals
    let number = Math.floor(Number(value)).toString();

    // Format according to the Indian numbering system
    let lastThreeDigits = number.slice(-3);
    let otherDigits = number.slice(0, -3);

    if (otherDigits !== '') {
        lastThreeDigits = ',' + lastThreeDigits;
    }

    return otherDigits.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThreeDigits;
},

    nomineeRelations()
    {
        return [
            { id: '0', text: 'Select for following' },
            { id: 'father', text: 'Father' },
            { id: 'mother', text: 'Mother' },
            { id: 'wife', text: 'Wife' },
            { id: 'husband', text: 'Husband' },
            { id: 'daughter', text: 'Daughter' },
            { id: 'son', text: 'Son' },
            { id: 'brother', text: 'Brother' },
            { id: 'sister', text: 'Sister' },
        ];    
    },

    formatDateTime(createdAt,flag) {
        // this will return 16-dec-2024
        if(flag == "a")
        {
            const date = new Date(createdAt);
            const day = String(date.getDate()).padStart(2, '0'); // Ensure 2 digits
            const month = date.toLocaleString('en', { month: 'short' }).toLowerCase(); // Get short month name in lowercase
            const year = String(date.getFullYear()).slice(-2); // Last 2 digits of year

            return `${day}-${month}-${year}`;
        }
    },

    getDepartureStatusMeta(payload, options = {}) {
        const departureMoment = parseDepartureMoment(payload, options);
        return buildDepartureStatusMeta(departureMoment);
    },

    getFileType(filePath) {
        if (!filePath || typeof filePath !== 'string') {
            return 'unknown';
        }
        // Extract the file extension
        const extension = filePath.split('.').pop().toLowerCase();
        
        // Define image and PDF extensions
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        const pdfExtension = 'pdf';
        
        // Check the file type
        if (imageExtensions.includes(extension)) {
            return 'image';
        } else if (extension === pdfExtension) {
            return 'pdf';
        } else {
            return 'unknown';
        }
    },

    getCountryList()
    {
        return [
            { id: '0', text: 'Select for following' },
            { id: 'afghanistan', text: 'Afghanistan' },
            { id: 'albania', text: 'Albania' },
            { id: 'algeria', text: 'Algeria' },
            { id: 'andorra', text: 'Andorra' },
            { id: 'angola', text: 'Angola' },
            { id: 'antigua and barbuda', text: 'Antigua and Barbuda' },
            { id: 'argentina', text: 'Argentina' },
            { id: 'armenia', text: 'Armenia' },
            { id: 'australia', text: 'Australia' },
            { id: 'austria', text: 'Austria' },
            { id: 'azerbaijan', text: 'Azerbaijan' },
            { id: 'bahamas', text: 'Bahamas' },
            { id: 'bahrain', text: 'Bahrain' },
            { id: 'bangladesh', text: 'Bangladesh' },
            { id: 'barbados', text: 'Barbados' },
            { id: 'belarus', text: 'Belarus' },
            { id: 'belgium', text: 'Belgium' },
            { id: 'belize', text: 'Belize' },
            { id: 'benin', text: 'Benin' },
            { id: 'bhutan', text: 'Bhutan' },
            { id: 'bolivia', text: 'Bolivia' },
            { id: 'bosnia and herzegovina', text: 'Bosnia and Herzegovina' },
            { id: 'botswana', text: 'Botswana' },
            { id: 'brazil', text: 'Brazil' },
            { id: 'brunei', text: 'Brunei' },
            { id: 'bulgaria', text: 'Bulgaria' },
            { id: 'burkina faso', text: 'Burkina Faso' },
            { id: 'burundi', text: 'Burundi' },
            { id: 'cambodia', text: 'Cambodia' },
            { id: 'cameroon', text: 'Cameroon' },
            { id: 'canada', text: 'Canada' },
            { id: 'cape verde', text: 'Cape Verde' },
            { id: 'central african republic', text: 'Central African Republic' },
            { id: 'chad', text: 'Chad' },
            { id: 'chile', text: 'Chile' },
            { id: 'china', text: 'China' },
            { id: 'colombia', text: 'Colombia' },
            { id: 'comoros', text: 'Comoros' },
            { id: 'congo', text: 'Congo' },
            { id: 'costa rica', text: 'Costa Rica' },
            { id: 'croatia', text: 'Croatia' },
            { id: 'cuba', text: 'Cuba' },
            { id: 'cyprus', text: 'Cyprus' },
            { id: 'czech republic', text: 'Czech Republic' },
            { id: 'denmark', text: 'Denmark' },
            { id: 'djibouti', text: 'Djibouti' },
            { id: 'dominica', text: 'Dominica' },
            { id: 'dominican republic', text: 'Dominican Republic' },
            { id: 'ecuador', text: 'Ecuador' },
            { id: 'egypt', text: 'Egypt' },
            { id: 'el salvador', text: 'El Salvador' },
            { id: 'equatorial guinea', text: 'Equatorial Guinea' },
            { id: 'eritrea', text: 'Eritrea' },
            { id: 'estonia', text: 'Estonia' },
            { id: 'ethiopia', text: 'Ethiopia' },
            { id: 'fiji', text: 'Fiji' },
            { id: 'finland', text: 'Finland' },
            { id: 'france', text: 'France' },
            { id: 'gabon', text: 'Gabon' },
            { id: 'gambia', text: 'Gambia' },
            { id: 'georgia', text: 'Georgia' },
            { id: 'germany', text: 'Germany' },
            { id: 'ghana', text: 'Ghana' },
            { id: 'greece', text: 'Greece' },
            { id: 'grenada', text: 'Grenada' },
            { id: 'guatemala', text: 'Guatemala' },
            { id: 'guinea', text: 'Guinea' },
            { id: 'guinea-bissau', text: 'Guinea-Bissau' },
            { id: 'guyana', text: 'Guyana' },
            { id: 'haiti', text: 'Haiti' },
            { id: 'honduras', text: 'Honduras' },
            { id: 'hungary', text: 'Hungary' },
            { id: 'iceland', text: 'Iceland' },
            { id: 'india', text: 'India' },
            { id: 'indonesia', text: 'Indonesia' },
            { id: 'iran', text: 'Iran' },
            { id: 'iraq', text: 'Iraq' },
            { id: 'ireland', text: 'Ireland' },
            { id: 'israel', text: 'Israel' },
            { id: 'italy', text: 'Italy' },
            { id: 'jamaica', text: 'Jamaica' },
            { id: 'japan', text: 'Japan' },
            { id: 'jordan', text: 'Jordan' },
            { id: 'kazakhstan', text: 'Kazakhstan' },
            { id: 'kenya', text: 'Kenya' },
            { id: 'korea', text: 'Korea' },
            { id: 'kuwait', text: 'Kuwait' },
            { id: 'kyrgyzstan', text: 'Kyrgyzstan' },
            { id: 'laos', text: 'Laos' },
            { id: 'latvia', text: 'Latvia' },
            { id: 'lebanon', text: 'Lebanon' },
            { id: 'lesotho', text: 'Lesotho' },
            { id: 'liberia', text: 'Liberia' },
            { id: 'libya', text: 'Libya' },
            { id: 'liechtenstein', text: 'Liechtenstein' },
            { id: 'lithuania', text: 'Lithuania' },
            { id: 'luxembourg', text: 'Luxembourg' },
            { id: 'madagascar', text: 'Madagascar' },
            { id: 'malawi', text: 'Malawi' },
            { id: 'malaysia', text: 'Malaysia' },
            { id: 'maldives', text: 'Maldives' },
            { id: 'mali', text: 'Mali' },
            { id: 'malta', text: 'Malta' },
            { id: 'mexico', text: 'Mexico' },
            { id: 'moldova', text: 'Moldova' },
            { id: 'monaco', text: 'Monaco' },
            { id: 'mongolia', text: 'Mongolia' },
            { id: 'morocco', text: 'Morocco' },
            { id: 'mozambique', text: 'Mozambique' },
            { id: 'myanmar', text: 'Myanmar' },
            { id: 'namibia', text: 'Namibia' },
            { id: 'nepal', text: 'Nepal' },
            { id: 'netherlands', text: 'Netherlands' },
            { id: 'new zealand', text: 'New Zealand' },
            { id: 'nigeria', text: 'Nigeria' },
            { id: 'north korea', text: 'North Korea' },
            { id: 'norway', text: 'Norway' },
            { id: 'oman', text: 'Oman' },
            { id: 'pakistan', text: 'Pakistan' },
            { id: 'palestine', text: 'Palestine' },
            { id: 'panama', text: 'Panama' },
            { id: 'paraguay', text: 'Paraguay' },
            { id: 'peru', text: 'Peru' },
            { id: 'philippines', text: 'Philippines' },
            { id: 'poland', text: 'Poland' },
            { id: 'portugal', text: 'Portugal' },
            { id: 'qatar', text: 'Qatar' },
            { id: 'romania', text: 'Romania' },
            { id: 'russia', text: 'Russia' },
            { id: 'rwanda', text: 'Rwanda' },
            { id: 'saudi arabia', text: 'Saudi Arabia' },
            { id: 'senegal', text: 'Senegal' },
            { id: 'serbia', text: 'Serbia' },
            { id: 'seychelles', text: 'Seychelles' },
            { id: 'sierra leone', text: 'Sierra Leone' },
            { id: 'singapore', text: 'Singapore' },
            { id: 'somalia', text: 'Somalia' },
            { id: 'spain', text: 'Spain' },
            { id: 'sri lanka', text: 'Sri Lanka' },
            { id: 'sudan', text: 'Sudan' },
            { id: 'suriname', text: 'Suriname' },
            { id: 'sweden', text: 'Sweden' },
            { id: 'switzerland', text: 'Switzerland' },
            { id: 'syria', text: 'Syria' },
            { id: 'taiwan', text: 'Taiwan' },
            { id: 'tajikistan', text: 'Tajikistan' },
            { id: 'tanzania', text: 'Tanzania' },
            { id: 'thailand', text: 'Thailand' },
            { id: 'togo', text: 'Togo' },
            { id: 'tonga', text: 'Tonga' },
            { id: 'tunisia', text: 'Tunisia' },
            { id: 'turkey', text: 'Turkey' },
            { id: 'turkmenistan', text: 'Turkmenistan' },
            { id: 'uganda', text: 'Uganda' },
            { id: 'ukraine', text: 'Ukraine' },
            { id: 'united arab emirates', text: 'United Arab Emirates' },
            { id: 'united kingdom', text: 'United Kingdom' },
            { id: 'united states', text: 'United States' },
            { id: 'uruguay', text: 'Uruguay' },
            { id: 'uzbekistan', text: 'Uzbekistan' },
            { id: 'venezuela', text: 'Venezuela' },
            { id: 'vietnam', text: 'Vietnam' },
            { id: 'yemen', text: 'Yemen' },
            { id: 'zambia', text: 'Zambia' },
            { id: 'zimbabwe', text: 'Zimbabwe' },
        ];    
    }
}
