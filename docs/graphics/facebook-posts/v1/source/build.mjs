// Builds posts.json and one HTML poster per post (1080x1080 design).
// Usage: node build.mjs   (run from this folder)
// Render each HTML at a 540x540 viewport (the posters scale themselves) or at 1080x1080 in any browser.
import { writeFileSync, mkdirSync } from 'node:fs';

const PHONE = '+880 1575-888775';
const SITE = 'datatechbd.com';

// kind: feature | pain | tip | demo | brand | bn
// [icon, kind, en[headline, sub, b1, b2, b3], bn[headline, sub, b1, b2, b3], hashtags]
const P = [
    ['users', 'feature', ['One patient. One record.', 'Register in seconds and never create a duplicate again.', 'Duplicate warning on mobile and name', 'Family members linked on one mobile', 'Allergy alerts on every screen'], ['একজন রোগী, একটিই রেকর্ড', 'কয়েক সেকেন্ডে নিবন্ধন, কখনও ডুপ্লিকেট নয়।', 'মোবাইল ও নামে ডুপ্লিকেট সতর্কতা', 'এক মোবাইলে পরিবারের সদস্য যুক্ত', 'প্রতিটি স্ক্রিনে অ্যালার্জি সতর্কতা'], ['PatientRegistration', 'DiagnosticCenter']],
    ['file', 'feature', ['Built for the busy counter.', 'Search, order, take cash and print without leaving the screen.', 'Type-ahead patient and test search', 'Invoice, receipt and labels in one action', 'Keyboard shortcuts for speed'], ['ব্যস্ত কাউন্টারের জন্য তৈরি', 'সার্চ, অর্ডার, ক্যাশ ও প্রিন্ট, এক স্ক্রিনেই।', 'রোগী ও টেস্টের দ্রুত সার্চ', 'এক ক্লিকে ইনভয়েস, রসিদ ও লেবেল', 'কিবোর্ড শর্টকাটে গতি'], ['Billing', 'ReceptionDesk']],
    ['calendar', 'feature', ['Serials without the shouting.', 'Slots, tokens and a live doctor queue.', 'Only free slots are offered', 'Token number issued automatically', 'Doctor sees the next patient'], ['চিৎকার ছাড়াই সিরিয়াল', 'স্লট, টোকেন ও ডাক্তারের লাইভ কিউ।', 'শুধু ফাঁকা স্লট দেখায়', 'টোকেন নম্বর স্বয়ংক্রিয়', 'ডাক্তার পরবর্তী রোগী দেখেন'], ['Appointments', 'ClinicManagement']],
    ['phone', 'feature', ['Let patients book from their phone.', 'Pick a doctor, a day and a time. Done.', 'No phone call, no waiting in line', 'Free slots only, token on screen', 'English and Bangla'], ['ফোন থেকেই বুকিং', 'ডাক্তার, দিন ও সময় বেছে নিন। ব্যস, শেষ।', 'ফোন বা লাইনে দাঁড়ানো ছাড়াই', 'ফাঁকা স্লট, স্ক্রিনেই টোকেন', 'ইংরেজি ও বাংলা'], ['OnlineBooking', 'Bangladesh']],
    ['flask', 'feature', ['Your price list, always in order.', 'Departments, tests, parameters and packages in one catalog.', 'Preparation notes shown at the counter', 'Turnaround time per test', 'Optional branch price lists'], ['আপনার মূল্য তালিকা সবসময় গোছানো', 'বিভাগ, টেস্ট, প্যারামিটার ও প্যাকেজ এক ক্যাটালগে।', 'কাউন্টারে প্রস্তুতির নোট', 'প্রতিটি টেস্টের রিপোর্ট সময়', 'শাখাভেদে মূল্য তালিকা'], ['TestCatalog', 'LabSoftware']],
    ['activity', 'feature', ['The right range for every patient.', 'Reference ranges by age and sex, flagged automatically.', 'Normal, high, low and critical flags', 'Critical limits per parameter', 'Range saved with the result'], ['প্রতিটি রোগীর জন্য সঠিক রেঞ্জ', 'বয়স ও লিঙ্গভেদে রেফারেন্স রেঞ্জ, স্বয়ংক্রিয় ফ্ল্যাগ।', 'স্বাভাবিক, বেশি, কম ও ক্রিটিক্যাল চিহ্ন', 'প্রতি প্যারামিটারে ক্রিটিক্যাল সীমা', 'ফলাফলের সাথে রেঞ্জ সংরক্ষিত'], ['Pathology', 'LabAutomation']],
    ['barcode', 'feature', ['Every sample labeled. No mix-ups.', 'Barcode labels from collection to the lab bench.', 'Code 128 labels, printed in one click', 'Receive, reject and recollect with a reason', 'Scan a label to open the sample'], ['প্রতিটি নমুনায় লেবেল, গুলিয়ে যাওয়ার সুযোগ নেই', 'সংগ্রহ থেকে ল্যাব পর্যন্ত বারকোড লেবেল।', 'এক ক্লিকে কোড ১২৮ লেবেল প্রিন্ট', 'কারণসহ গ্রহণ, বাতিল ও পুনঃসংগ্রহ', 'লেবেল স্ক্যান করলেই নমুনা খোলে'], ['SampleTracking', 'LabSafety']],
    ['clock', 'feature', ['See what is waiting. See what is late.', 'A lab worklist with turnaround countdown.', 'Time left on every sample', 'Urgent samples pinned to the top', 'Filter by department and status'], ['কী অপেক্ষায়, কী দেরিতে, সব দেখুন', 'টিএটি কাউন্টডাউনসহ ল্যাব ওয়ার্কলিস্ট।', 'প্রতিটি নমুনায় বাকি সময়', 'জরুরি নমুনা সবার উপরে', 'বিভাগ ও অবস্থা অনুযায়ী ফিল্টার'], ['LabWorkflow', 'TurnaroundTime']],
    ['file', 'feature', ['Enter results at typing speed.', 'A result grid made for keyboards.', 'Tab and Enter move through fields', 'Previous result shown beside the new one', 'Calculated parameters filled for you'], ['টাইপিংয়ের গতিতে ফলাফল এন্ট্রি', 'কিবোর্ডের জন্য তৈরি রেজাল্ট গ্রিড।', 'ট্যাব ও এন্টারে ঘর বদল', 'আগের ফলাফল পাশেই দেখা যায়', 'হিসাবকৃত প্যারামিটার নিজে থেকে পূরণ'], ['ResultEntry', 'MedicalLab']],
    ['shield', 'feature', ['Critical results never slip through.', 'A critical value must be acknowledged before it moves on.', 'Who was informed and when', 'Logged for every critical value', 'A dedicated critical results log'], ['ক্রিটিক্যাল ফলাফল কখনও বাদ পড়ে না', 'স্বীকৃতি ছাড়া ক্রিটিক্যাল মান এগোয় না।', 'কাকে কখন জানানো হয়েছে', 'প্রতিটি ক্রিটিক্যাল মানের লগ', 'আলাদা ক্রিটিক্যাল ফলাফল তালিকা'], ['PatientSafety', 'CriticalValues']],
    ['file', 'feature', ['Findings and impression, done right.', 'A narrative editor for ultrasound, X-ray and ECG.', 'Quick phrases with a slash shortcut', 'Report templates per department', 'Attach images to the result'], ['ফাইন্ডিংস ও ইমপ্রেশন, সঠিকভাবে', 'আল্ট্রাসাউন্ড, এক্স-রে ও ইসিজির জন্য নারেটিভ এডিটর।', 'স্ল্যাশ শর্টকাটে কুইক ফ্রেজ', 'বিভাগভিত্তিক রিপোর্ট টেমপ্লেট', 'ফলাফলে ছবি সংযুক্ত করুন'], ['Radiology', 'Imaging']],
    ['box', 'feature', ['Health packages in two clicks.', 'Bundle popular tests into one price.', 'A package expands into its tests', 'Package price kept on the invoice', 'Shown on your public website'], ['দুই ক্লিকে হেলথ প্যাকেজ', 'জনপ্রিয় টেস্ট এক দামে একসাথে।', 'প্যাকেজ খুললে টেস্টের তালিকা', 'ইনভয়েসে প্যাকেজ মূল্য', 'পাবলিক ওয়েবসাইটেও দেখা যায়'], ['HealthCheckup', 'DiagnosticPackages']],
    ['shield', 'feature', ['Discounts with a paper trail.', 'Limits, reasons and manager approval built in.', 'Over-limit discounts wait for approval', 'Manager PIN at the counter', 'Every override is logged'], ['প্রতিটি ছাড়ের হিসাব থাকে', 'সীমা, কারণ ও ম্যানেজারের অনুমোদন যুক্ত।', 'সীমার বেশি ছাড় অনুমোদনের অপেক্ষায়', 'কাউন্টারে ম্যানেজার পিন', 'প্রতিটি ওভাররাইড লগ হয়'], ['Accountability', 'Billing']],
    ['rotate', 'feature', ['Refunds that need a reason and a yes.', 'No silent refunds. Ever.', 'Reason is required', 'Manager approval is required', 'Full history on the invoice'], ['কারণ ও অনুমোদন ছাড়া রিফান্ড নয়', 'নীরবে কোনো রিফান্ড হয় না।', 'কারণ লিখতে হয়', 'ম্যানেজারের অনুমোদন লাগে', 'ইনভয়েসে পূর্ণ ইতিহাস'], ['Refunds', 'InternalControl']],
    ['wallet', 'feature', ['Part payment today, balance later.', 'The due is calculated, never typed.', 'Partial payments with money receipts', 'Due shown on the next visit', 'Overdue list with aging'], ['আজ আংশিক, বাকিটা পরে', 'বকেয়া হিসাব হয় নিজে, হাতে লিখতে হয় না।', 'আংশিক পেমেন্ট ও মানি রিসিট', 'পরবর্তী ভিজিটে বকেয়া দেখা যায়', 'এজিংসহ বকেয়া তালিকা'], ['Dues', 'CashManagement']],
    ['building', 'feature', ['Many branches. One owner view.', 'Switch branches in one click.', 'A branch switcher on every screen', 'Staff assigned to branches', 'Shared catalog, separate data'], ['অনেক শাখা, মালিকের এক দৃষ্টি', 'এক ক্লিকে শাখা বদল।', 'প্রতিটি স্ক্রিনে শাখা সুইচার', 'কর্মী শাখাভিত্তিক নিযুক্ত', 'একই ক্যাটালগ, আলাদা ডেটা'], ['MultiBranch', 'BusinessSoftware']],
    ['lock', 'feature', ['Branch staff see only their branch.', 'Isolation that is tested, not just promised.', 'Cross-branch access returns not found', 'Checked by automated tests', 'Owners see everything'], ['শাখার কর্মী শুধু নিজ শাখা দেখেন', 'দাবি নয়, পরীক্ষিত পৃথকীকরণ।', 'অন্য শাখার তথ্য খুললে পাওয়া যায় না', 'স্বয়ংক্রিয় টেস্টে যাচাই করা', 'মালিক সবকিছু দেখেন'], ['DataSecurity', 'AccessControl']],
    ['users', 'feature', ['Eight roles. The right screens for each.', 'Owner, manager, reception, collector, lab, pathologist, doctor, accountant.', 'Menus match permissions', 'Least privilege by default', 'Easy to assign and change'], ['আটটি ভূমিকা, প্রত্যেকের জন্য সঠিক স্ক্রিন', 'মালিক, ম্যানেজার, রিসেপশন, কালেক্টর, ল্যাব, প্যাথলজিস্ট, ডাক্তার, হিসাবরক্ষক।', 'অনুমতি অনুযায়ী মেনু', 'ডিফল্টভাবে ন্যূনতম অ্যাক্সেস', 'ভূমিকা দেওয়া ও বদল সহজ'], ['UserRoles', 'Permissions']],
    ['eye', 'feature', ['Know who opened which patient record.', 'An access log for every view.', 'User, time and IP address', 'Patient record, report and history views', 'Searchable by patient ID'], ['কে কোন রোগীর রেকর্ড খুলেছে, জানুন', 'প্রতিটি দেখার অ্যাক্সেস লগ।', 'ব্যবহারকারী, সময় ও আইপি', 'রেকর্ড, রিপোর্ট ও হিস্ট্রি দেখার লগ', 'রোগী আইডি দিয়ে সার্চ'], ['PatientPrivacy', 'AuditTrail']],
    ['globe', 'feature', ['English and Bangla, one click.', 'The whole interface speaks your language.', 'Screens, receipts and messages', 'Bangla names supported', 'Bangla digits when you want them'], ['ইংরেজি ও বাংলা, এক ক্লিকে', 'পুরো সফটওয়্যার আপনার ভাষায়।', 'স্ক্রিন, রসিদ ও বার্তা', 'বাংলা নাম সমর্থিত', 'চাইলে বাংলা সংখ্যা'], ['Bangla', 'BanglaSoftware']],
    ['fingerprint', 'hr', ['Fingerprint punches flow in by themselves.', 'ZKTeco devices push attendance straight to DT Medix.', 'Works with ZKTeco push (ADMS)', 'Or import the device file', 'No retyping, no lost punches'], ['আঙুলের ছাপের উপস্থিতি নিজে থেকেই আসে', 'জেডকেটেকো ডিভাইস সরাসরি ডিটি মেডিক্সে উপস্থিতি পাঠায়।', 'জেডকেটেকো পুশ (ADMS) সমর্থিত', 'অথবা ডিভাইস ফাইল ইমপোর্ট', 'আবার টাইপ নয়, হারানো পাঞ্চ নয়'], ['ZKTeco', 'EmployeeAttendance']],
    ['edit', 'hr', ['No device? Mark attendance by hand.', 'Manual entry that device data can never overwrite.', 'Status, in and out times, a note', 'Every manual change is logged', 'Remove it to rebuild from punches'], ['ডিভাইস নেই? হাতেই উপস্থিতি দিন', 'ম্যানুয়াল এন্ট্রি ডিভাইস ডেটা দিয়ে বদলায় না।', 'অবস্থা, প্রবেশ-প্রস্থান ও নোট', 'প্রতিটি পরিবর্তন লগ হয়', 'সরালে পাঞ্চ থেকে আবার তৈরি'], ['Attendance', 'HRSoftware']],
    ['clock', 'hr', ['Late minutes and overtime, calculated.', 'Shifts, grace time and weekly offs.', 'Late, early and overtime minutes', 'Holidays and approved leave counted', 'A monthly attendance sheet'], ['দেরি ও ওভারটাইম নিজে হিসাব হয়', 'শিফট, গ্রেস টাইম ও সাপ্তাহিক ছুটি।', 'দেরি, আগে যাওয়া ও ওভারটাইম মিনিট', 'ছুটির দিন ও অনুমোদিত ছুটি ধরা হয়', 'মাসিক উপস্থিতি শিট'], ['ShiftManagement', 'Overtime']],
    ['calendar', 'hr', ['Leave requests, approved in a click.', 'Approved leave flows into attendance and payroll.', 'Casual, sick, annual and unpaid', 'Approve or reject with one click', 'No overlapping requests'], ['এক ক্লিকে ছুটির অনুমোদন', 'অনুমোদিত ছুটি উপস্থিতি ও বেতনে যুক্ত হয়।', 'নৈমিত্তিক, অসুস্থতা, বার্ষিক ও বিনা বেতন', 'এক ক্লিকে অনুমোদন বা প্রত্যাখ্যান', 'একই সময়ে দুটি আবেদন নয়'], ['LeaveManagement', 'HR']],
    ['wallet', 'hr', ['Payroll from attendance, not guesswork.', 'Monthly salary runs prepared for you.', 'Basic salary, allowances and overtime', 'Absence and late deductions', 'Advance and other deductions'], ['আন্দাজ নয়, উপস্থিতি থেকে বেতন', 'প্রতি মাসের বেতন আপনার জন্য তৈরি।', 'মূল বেতন, ভাতা ও ওভারটাইম', 'অনুপস্থিতি ও দেরির কর্তন', 'অগ্রিম ও অন্যান্য কর্তন'], ['Payroll', 'SalarySheet']],
    ['file', 'hr', ['Printable payslips, locked after finalizing.', 'Once finalized, the numbers cannot change.', 'Earnings and deductions on one page', 'Mark each salary paid with a reference', 'Payroll register to print'], ['প্রিন্টযোগ্য পে-স্লিপ, চূড়ান্ত হলে লক', 'চূড়ান্ত করার পর অঙ্ক বদলানো যায় না।', 'আয় ও কর্তন এক পাতায়', 'রেফারেন্সসহ বেতন পরিশোধ চিহ্নিত', 'প্রিন্টযোগ্য পে-রোল রেজিস্টার'], ['Payslip', 'PayrollSoftware']],
    ['globe', 'brand', ['A website for your center, included.', 'Home, services, tests, doctors, booking and more.', 'Price list and health packages', 'Online doctor booking', 'FAQ, patient guide and privacy policy'], ['আপনার সেন্টারের জন্য ওয়েবসাইট, সাথেই', 'হোম, সেবা, টেস্ট, ডাক্তার, বুকিং আরও অনেক কিছু।', 'মূল্য তালিকা ও হেলথ প্যাকেজ', 'অনলাইনে ডাক্তারের বুকিং', 'এফএকিউ, রোগী নির্দেশিকা ও গোপনীয়তা নীতি'], ['DiagnosticWebsite', 'Branding']],
    ['book', 'brand', ['Educate patients before they arrive.', 'A patient guide that answers the common questions.', 'How to prepare for common tests', 'What to bring', 'How to read normal, high and low flags'], ['আসার আগেই রোগীকে জানান', 'সাধারণ প্রশ্নের উত্তর দেয় রোগী নির্দেশিকা।', 'সাধারণ পরীক্ষার প্রস্তুতি', 'কী সাথে আনতে হবে', 'স্বাভাবিক, বেশি ও কম চিহ্ন কীভাবে বুঝবেন'], ['PatientEducation', 'HealthAwareness']],
    ['home', 'brand', ['Explain home collection clearly.', 'A page that shows the steps and eligible tests.', 'Four simple steps', 'Eligible tests with preparation notes', 'A call-to-action for your front desk'], ['হোম কালেকশন পরিষ্কারভাবে জানান', 'ধাপ ও যোগ্য টেস্ট দেখানো একটি পাতা।', 'চারটি সহজ ধাপ', 'প্রস্তুতির নোটসহ যোগ্য টেস্ট', 'ফ্রন্ট ডেস্কের জন্য কল টু অ্যাকশন'], ['HomeCollection', 'PatientConvenience']],
    ['bolt', 'demo', ['Free demo. Try it yourself!', 'Sign in and explore every feature. Demo data resets daily at 4:00 AM, Dhaka time.', 'No installation needed', 'Real screens with real sample data', `Want it for your business? Call ${PHONE}`], ['ফ্রি ডেমো, নিজেই চালিয়ে দেখুন!', 'সাইন ইন করে সব ফিচার দেখুন। ডেমো ডেটা প্রতিদিন ভোর ৪:০০টায় রিসেট হয়।', 'কিছু ইনস্টল করতে হবে না', 'আসল স্ক্রিন, নমুনা ডেটা', `আপনার ব্যবসার জন্য চান? কল করুন ${PHONE}`], ['FreeDemo', 'TryItYourself']],
    ['users', 'demo', ['One click. Eight roles.', 'Sign in as owner, reception, lab, doctor or accountant.', 'Demo accounts on the sign-in page', 'See each role\'s own menu', 'Explore at your own pace'], ['এক ক্লিকে আটটি ভূমিকা', 'মালিক, রিসেপশন, ল্যাব, ডাক্তার বা হিসাবরক্ষক হিসেবে সাইন ইন।', 'সাইন-ইন পাতায় ডেমো অ্যাকাউন্ট', 'প্রতিটি ভূমিকার নিজস্ব মেনু', 'নিজের গতিতে ঘুরে দেখুন'], ['DemoAccounts', 'SoftwareDemo']],
    ['building', 'brand', ['Software that runs business.', 'DT Medix is a product of Datatech BD Ltd.', 'Built for diagnostic centers and clinics', 'Made in Bangladesh, for Bangladesh', 'Backed by local support'], ['সফটওয়্যার যা ব্যবসা চালায়', 'ডিটি মেডিক্স ডাটাটেক বিডি লিমিটেডের পণ্য।', 'ডায়াগনস্টিক সেন্টার ও ক্লিনিকের জন্য', 'বাংলাদেশে তৈরি, বাংলাদেশের জন্য', 'স্থানীয় সহায়তা'], ['DatatechBD', 'SoftwareThatRunsBusiness']],
    ['moon', 'brand', ['Light, dark and touch-friendly.', 'Comfortable on a counter PC and a collection tablet.', 'Light and dark themes', 'Large touch targets for tablets', 'Fast, compact tables'], ['লাইট, ডার্ক ও টাচ-বান্ধব', 'কাউন্টারের পিসি ও কালেকশন ট্যাবলেটে আরামদায়ক।', 'লাইট ও ডার্ক থিম', 'ট্যাবলেটের জন্য বড় টাচ বোতাম', 'দ্রুত, কমপ্যাক্ট টেবিল'], ['UIUX', 'Tablet']],
    ['message', 'brand', ['Messages that never carry results.', 'Templates in English and Bangla with safe placeholders.', 'Appointment, sample and payment notices', 'Results and diagnoses are blocked', 'Edit the text per center'], ['বার্তায় কখনও ফলাফল নয়', 'নিরাপদ প্লেসহোল্ডারসহ ইংরেজি ও বাংলা টেমপ্লেট।', 'অ্যাপয়েন্টমেন্ট, নমুনা ও পেমেন্টের বিজ্ঞপ্তি', 'ফলাফল ও রোগনির্ণয় আটকানো', 'প্রতিটি সেন্টারে লেখা বদলানো যায়'], ['PatientPrivacy', 'MessageTemplates']],
    ['file', 'pain', ['Still using paper registers?', 'Move to one connected flow: patient, order, lab, report, payment.', 'Search any patient in a moment', 'No more lost slips', 'Daily totals that add up'], ['এখনও কাগজের খাতা?', 'এক সংযুক্ত প্রবাহে আসুন: রোগী, অর্ডার, ল্যাব, রিপোর্ট, পেমেন্ট।', 'মুহূর্তে যেকোনো রোগী খুঁজুন', 'হারানো স্লিপ নয়', 'দৈনিক হিসাব মেলে'], ['GoDigital', 'DiagnosticCenter']],
    ['barcode', 'pain', ['Lost a sample?', 'Barcode every tube and always know where it is.', 'Collected, received or rejected', 'Who handled it and when', 'Recollect with one click'], ['নমুনা হারিয়ে গেছে?', 'প্রতিটি টিউবে বারকোড, সবসময় জানুন কোথায়।', 'সংগৃহীত, গৃহীত বা বাতিল', 'কে কখন হাতে নিয়েছে', 'এক ক্লিকে পুনঃসংগ্রহ'], ['SampleTracking', 'LabQuality']],
    ['user', 'pain', ['Wrong patient, wrong report?', 'A patient banner on every screen keeps you sure.', 'Name, age, sex and patient ID', 'Allergy alerts always visible', 'Duplicate checks at registration'], ['ভুল রোগী, ভুল রিপোর্ট?', 'প্রতিটি স্ক্রিনে রোগীর ব্যানার আপনাকে নিশ্চিত রাখে।', 'নাম, বয়স, লিঙ্গ ও রোগী আইডি', 'অ্যালার্জি সতর্কতা সবসময় দৃশ্যমান', 'নিবন্ধনেই ডুপ্লিকেট যাচাই'], ['PatientSafety', 'Accuracy']],
    ['wallet', 'pain', ['Month-end salary headache?', 'Let attendance build the payroll for you.', 'Attendance counted automatically', 'Deductions calculated', 'Payslips ready to print'], ['মাস শেষে বেতনের মাথাব্যথা?', 'উপস্থিতি থেকেই পে-রোল তৈরি হোক।', 'উপস্থিতি স্বয়ংক্রিয় গণনা', 'কর্তন হিসাব করা', 'প্রিন্টের জন্য পে-স্লিপ প্রস্তুত'], ['Payroll', 'SmallBusiness']],
    ['history', 'pain', ['Cannot tell who changed what?', 'An activity log answers it.', 'Discounts, refunds and payroll actions', 'Who did it and when', 'Nothing is silently edited'], ['কে কী বদলেছে বোঝা যায় না?', 'অ্যাক্টিভিটি লগে উত্তর পাবেন।', 'ছাড়, রিফান্ড ও পে-রোলের কাজ', 'কে করেছে ও কখন', 'নীরবে কিছু বদলায় না'], ['AuditTrail', 'Transparency']],
    ['clock', 'pain', ['Waiting patients losing patience?', 'Tokens and a live queue keep the line calm.', 'Token number on booking', 'Doctor queue with waiting time', 'Fewer questions at the counter'], ['অপেক্ষমাণ রোগীরা অধৈর্য?', 'টোকেন ও লাইভ কিউ লাইন শান্ত রাখে।', 'বুকিংয়েই টোকেন নম্বর', 'অপেক্ষার সময়সহ ডাক্তারের কিউ', 'কাউন্টারে প্রশ্ন কম'], ['PatientExperience', 'ClinicQueue']],
    ['tag', 'tip', ['Publish your price list.', 'Patients trust centers that show prices up front.', 'Keep the list on your website', 'Update it when prices change', 'Show packages too'], ['আপনার মূল্য তালিকা প্রকাশ করুন', 'যে সেন্টার আগেই দাম দেখায়, রোগী তাকে বিশ্বাস করে।', 'ওয়েবসাইটে তালিকা রাখুন', 'দাম বদলালে হালনাগাদ করুন', 'প্যাকেজও দেখান'], ['DiagnosticTips', 'Trust']],
    ['barcode', 'tip', ['Label samples at the patient.', 'Never label a tube away from the person it came from.', 'Print the label at collection', 'Check name and ID aloud', 'Scan again at the lab'], ['রোগীর সামনেই নমুনায় লেবেল দিন', 'যার নমুনা, তার সামনে ছাড়া লেবেল নয়।', 'সংগ্রহের সময়ই লেবেল প্রিন্ট', 'নাম ও আইডি মুখে মিলিয়ে নিন', 'ল্যাবে আবার স্ক্যান'], ['LabTips', 'SampleSafety']],
    ['clock', 'tip', ['Track turnaround time every week.', 'What you measure improves.', 'Check overdue samples daily', 'Pin urgent tests', 'Talk to the slowest department'], ['প্রতি সপ্তাহে টিএটি দেখুন', 'যা মাপা হয়, তা-ই উন্নত হয়।', 'প্রতিদিন দেরির নমুনা দেখুন', 'জরুরি টেস্ট পিন করুন', 'সবচেয়ে ধীর বিভাগের সাথে কথা বলুন'], ['ManagementTips', 'TAT']],
    ['shield', 'tip', ['Review discounts every week.', 'Small discounts add up quickly.', 'Look at approved and rejected requests', 'Ask for a reason on every override', 'Set a sensible limit'], ['প্রতি সপ্তাহে ছাড় পর্যালোচনা করুন', 'ছোট ছাড় জমে বড় হয়ে যায়।', 'অনুমোদিত ও প্রত্যাখ্যাত আবেদন দেখুন', 'প্রতিটি ওভাররাইডে কারণ চান', 'যুক্তিসঙ্গত সীমা রাখুন'], ['OwnerTips', 'CostControl']],
    ['database', 'tip', ['Back up daily. Test the restore.', 'A backup you never restored is only a hope.', 'Automate the backup', 'Practice a restore', 'Keep a copy off the server'], ['প্রতিদিন ব্যাকআপ, রিস্টোর পরীক্ষা করুন', 'যে ব্যাকআপ কখনও রিস্টোর হয়নি, তা কেবল আশা।', 'ব্যাকআপ স্বয়ংক্রিয় করুন', 'রিস্টোর অনুশীলন করুন', 'সার্ভারের বাইরেও একটি কপি রাখুন'], ['DataBackup', 'ITTips']],
    ['lock', 'tip', ['Never send results by text message.', 'Send a short notice and a secure link instead.', 'Results are private health data', 'Keep SMS short and generic', 'Log who opens a report'], ['এসএমএসে কখনও ফলাফল পাঠাবেন না', 'পরিবর্তে ছোট বিজ্ঞপ্তি ও নিরাপদ লিংক পাঠান।', 'ফলাফল ব্যক্তিগত স্বাস্থ্য তথ্য', 'এসএমএস ছোট ও সাধারণ রাখুন', 'রিপোর্ট কে খুলল তার লগ রাখুন'], ['PatientPrivacy', 'DataProtection']],
    ['phone', 'demo', ['Ready to modernize your center?', 'Talk to Datatech BD Ltd. about DT Medix.', 'See a live demo', 'Get set up for your branches', 'Local support in Bangla and English'], ['আপনার সেন্টার আধুনিক করতে প্রস্তুত?', 'ডিটি মেডিক্স নিয়ে ডাটাটেক বিডি লিমিটেডের সাথে কথা বলুন।', 'লাইভ ডেমো দেখুন', 'আপনার শাখাগুলোর জন্য সেটআপ', 'বাংলা ও ইংরেজিতে স্থানীয় সহায়তা'], ['DTMedix', 'DatatechBD']],
    ['message', 'demo', ['Questions? Just ask.', 'We are happy to walk you through DT Medix.', `Call ${PHONE}`, `Visit ${SITE}`, 'Try the free demo first'], ['প্রশ্ন আছে? নির্দ্বিধায় জিজ্ঞাসা করুন', 'ডিটি মেডিক্স বুঝিয়ে দিতে আমরা প্রস্তুত।', `কল করুন ${PHONE}`, `ভিজিট করুন ${SITE}`, 'আগে ফ্রি ডেমো চালিয়ে দেখুন'], ['ContactUs', 'DTMedix']],
    ['hospital', 'bn', ['ডায়াগনস্টিক সেন্টারের জন্য সম্পূর্ণ সফটওয়্যার', 'রোগী থেকে রিপোর্ট, বিলিং থেকে বেতন, সব এক জায়গায়।', 'রোগী, অ্যাপয়েন্টমেন্ট ও বিলিং', 'ল্যাব, নমুনা ও ফলাফল', 'উপস্থিতি ও বেতন'], ['Complete software for diagnostic centers', 'From patient to report, billing to payroll, in one place.', 'Patients, appointments and billing', 'Lab, samples and results', 'Attendance and payroll'], ['ডিটিমেডিক্স', 'ডায়াগনস্টিক']],
    ['bolt', 'bn', ['ফ্রি ডেমো চালিয়ে দেখুন', 'সাইন ইন করে সব ফিচার ঘুরে দেখুন। ডেমো ডেটা প্রতিদিন ভোর ৪:০০টায় রিসেট হয়।', 'কিছু ইনস্টল করতে হবে না', 'আটটি ভূমিকায় এক ক্লিকে প্রবেশ', `আপনার ব্যবসার জন্য চান? কল করুন ${PHONE}`], ['Try the free demo', 'Sign in and explore every feature. Demo data resets daily at 4:00 AM.', 'No installation needed', 'One click into eight roles', `Want it for your business? Call ${PHONE}`], ['ফ্রিডেমো', 'ডিটিমেডিক্স']],
];

const themes = [
    { bg: 'linear-gradient(135deg,#0B63CE,#0A3F8F)', fg: '#FFFFFF', sub: '#D6E8FF', accent: '#38A8FF', chip: 'rgba(255,255,255,.14)' },
    { bg: 'linear-gradient(135deg,#0A1428,#14305F)', fg: '#FFFFFF', sub: '#AFC0D6', accent: '#38A8FF', chip: 'rgba(255,255,255,.10)' },
    { bg: 'linear-gradient(135deg,#F5F7FA,#DCE9FA)', fg: '#0A1428', sub: '#4A5568', accent: '#0B63CE', chip: 'rgba(11,99,206,.10)' },
    { bg: 'linear-gradient(135deg,#38A8FF,#0B63CE)', fg: '#FFFFFF', sub: '#EAF4FF', accent: '#0A1428', chip: 'rgba(10,20,40,.18)' },
];

const icons = {
    users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
    flask: '<path d="M9 3h6M10 3v6L4 19a2 2 0 0 0 1.7 3h12.6A2 2 0 0 0 20 19l-6-10V3"/>',
    activity: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    barcode: '<path d="M4 5v14M8 5v14M12 5v14M16 5v14M20 5v14M6 5v14" stroke-width="1.6"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
    box: '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v9"/>',
    rotate: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    wallet: '<path d="M20 12V8a2 2 0 0 0-2-2H5a2 2 0 0 1 0-4h13"/><path d="M3 5v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
    lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    eye: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    globe: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
    fingerprint: '<path d="M12 11a3 3 0 0 0-3 3c0 3 1 5 2 7M6 10a6 6 0 0 1 12 0c0 3 0 6-1.5 9M3 12a9 9 0 0 1 18 0M9.5 20c-.5-1-1-2.5-1-4M15 14c0 2 .3 3.5 1 5"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5z"/><path d="M4 19.5V22h16"/>',
    home: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
    bolt: '<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>',
    moon: '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
    message: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
    tag: '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
    database: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.7-4 3-9 3s-9-1.3-9-3M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/>',
    hospital: '<path d="M3 21V8l9-5 9 5v13zM12 8v8M8 12h8"/>',
};

const check = '<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
const kindLabel = { feature: 'Feature', hr: 'HR and payroll', brand: 'DT Medix', demo: 'Free demo', pain: 'Sound familiar?', tip: 'Owner tip', bn: 'ডিটি মেডিক্স' };
const kindLabelBn = { feature: 'ফিচার', hr: 'এইচআর ও পে-রোল', brand: 'ডিটি মেডিক্স', demo: 'ফ্রি ডেমো', pain: 'চেনা সমস্যা?', tip: 'মালিকের পরামর্শ', bn: 'ডিটি মেডিক্স' };

const css = (t) => `
@font-face{font-family:'Poppins';font-weight:600;src:url(fonts/poppins-latin-600.woff2) format('woff2')}
@font-face{font-family:'Poppins';font-weight:700;src:url(fonts/poppins-latin-700.woff2) format('woff2')}
@font-face{font-family:'Inter';font-weight:500;src:url(fonts/inter-latin-500-normal.woff2) format('woff2')}
@font-face{font-family:'Inter';font-weight:600;src:url(fonts/inter-latin-600-normal.woff2) format('woff2')}
@font-face{font-family:'Hind Siliguri';font-weight:500;src:url(fonts/hind-siliguri-bengali-500.woff2) format('woff2')}
@font-face{font-family:'Hind Siliguri';font-weight:700;src:url(fonts/hind-siliguri-bengali-700.woff2) format('woff2')}
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#fff}
.stage{width:1080px;height:1080px;transform-origin:0 0;position:relative;overflow:hidden;background:${t.bg};color:${t.fg};font-family:'Inter','Hind Siliguri',sans-serif}
.stage.bn{font-family:'Hind Siliguri','Poppins',sans-serif}
.ring1{position:absolute;right:-220px;top:-220px;width:620px;height:620px;border-radius:50%;background:${t.accent};opacity:.14}
.ring2{position:absolute;left:-180px;bottom:120px;width:420px;height:420px;border-radius:50%;background:${t.fg};opacity:.05}
.top{position:absolute;left:72px;right:72px;top:64px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:18px;font-family:'Poppins',sans-serif;font-weight:700;font-size:38px}
.brand svg{width:64px;height:64px}
.chip{background:${t.chip};padding:12px 26px;border-radius:999px;font-weight:600;font-size:26px;letter-spacing:.5px}
.content{position:absolute;left:72px;right:72px;top:176px;bottom:136px;display:flex;flex-direction:column;gap:22px}
.icon{flex:none;width:118px;height:118px;border-radius:30px;background:${t.chip};display:flex;align-items:center;justify-content:center;color:${t.accent}}
.icon svg{width:68px;height:68px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
h1{font-family:'Poppins','Hind Siliguri',sans-serif;font-weight:700;font-size:76px;line-height:1.14;letter-spacing:-1px}
h1.long{font-size:62px}
.stage.bn h1{font-family:'Hind Siliguri','Poppins',sans-serif;font-size:70px;line-height:1.22}
.stage.bn h1.long{font-size:58px}
p.sub{font-size:32px;line-height:1.38;color:${t.sub};font-weight:500}
ul{margin-top:auto;list-style:none;display:flex;flex-direction:column;gap:16px;background:${t.chip};border-radius:30px;padding:34px 40px}
li{display:flex;align-items:center;gap:18px;font-size:30px;font-weight:600;line-height:1.25}
li svg{flex:none;width:34px;height:34px;color:${t.accent}}
.foot{position:absolute;left:0;right:0;bottom:0;height:108px;background:rgba(0,0,0,.18);display:flex;align-items:center;justify-content:space-between;padding:0 72px;font-size:27px;font-weight:600}
.foot b{font-family:'Poppins',sans-serif;font-weight:700;font-size:30px}
`;

const mark = '<svg viewBox="0 0 120 120"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1A78E6"/><stop offset="1" stop-color="#0B52B0"/></linearGradient></defs><rect width="120" height="120" rx="28" fill="url(#g)"/><g transform="translate(18 18) scale(.84)"><rect x="38" y="16" width="24" height="68" rx="6" fill="#fff"/><rect x="16" y="38" width="68" height="24" rx="6" fill="#fff"/><g stroke="#0B63CE" stroke-width="5" stroke-linecap="round" fill="none"><path d="M20 50h18l7-18 10 34 7-16h18"/></g></g></svg>';

function html(post, lang) {
    const t = post.theme;
    const text = lang === 'bn' ? post.bn : post.en;
    const [headline, sub, ...bullets] = text;
    const label = lang === 'bn' ? kindLabelBn[post.kind] : kindLabel[post.kind];
    const items = bullets.map((b) => `<li>${check}<span>${b}</span></li>`).join('');
    const footL = lang === 'bn' ? 'ফ্রি ডেমো আছে' : 'Free demo available';
    return `<!doctype html><html lang="${lang}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${post.id}</title><style>${css(t)}</style></head><body>
<div class="stage ${lang === 'bn' ? 'bn' : ''}" id="s"><div class="ring1"></div><div class="ring2"></div>
<div class="top"><div class="brand">${mark}<span>DT Medix</span></div><div class="chip">${label}</div></div>
<div class="content"><div class="icon"><svg viewBox="0 0 24 24">${icons[post.icon]}</svg></div>
<h1 class="${headline.length > (lang === 'bn' ? 30 : 40) ? 'long' : ''}">${headline}</h1><p class="sub">${sub}</p><ul>${items}</ul></div>
<div class="foot"><span>${footL}</span><b>${PHONE}</b><span>${SITE}</span></div></div>
<script>function fit(){var s=document.getElementById('s'),k=Math.min(innerWidth,innerHeight)/1080;s.style.transform='scale('+k+')'}fit();addEventListener('resize',fit)</script>
</body></html>`;
}

const out = [];
mkdirSync('.', { recursive: true });
P.forEach(([icon, kind, first, second, tags], i) => {
    // Posts of kind "bn" are written Bangla first; keep en = English, bn = Bangla.
    const [en, bn] = kind === 'bn' ? [second, first] : [first, second];
    const n = String(i + 1).padStart(2, '0');
    const lang = kind === 'bn' ? 'bn' : 'en';
    const post = { id: `post-${n}`, icon, kind, theme: themes[i % themes.length], en, bn };
    writeFileSync(`post-${n}.html`, html(post, lang));
    const [h, s, ...b] = en;
    const [hb, sb, ...bb] = bn;
    const tagText = [...tags, 'DTMedix', 'DatatechBD'].map((x) => `#${x}`).join(' ');
    out.push({
        id: post.id,
        number: i + 1,
        type: kind,
        image: `images/post-${n}.jpg`,
        image_language: lang,
        icon,
        headline_en: h,
        subline_en: s,
        bullets_en: b,
        headline_bn: hb,
        subline_bn: sb,
        bullets_bn: bb,
        caption_en: `${h} ${s}\n\n${b.map((x) => `- ${x}`).join('\n')}\n\nTry the free demo or call ${PHONE}. ${SITE}\n\n${tagText}`,
        caption_bn: `${hb} ${sb}\n\n${bb.map((x) => `- ${x}`).join('\n')}\n\nফ্রি ডেমো চালিয়ে দেখুন বা কল করুন ${PHONE}। ${SITE}\n\n${tagText}`,
        hashtags: [...tags, 'DTMedix', 'DatatechBD'],
        call_to_action: { label_en: 'Try the free demo', label_bn: 'ফ্রি ডেমো চালিয়ে দেখুন', phone: PHONE, website: SITE },
        alt_text: `${h} - DT Medix, a product of Datatech BD Ltd.`,
        suggested_schedule_day: (i % 7) + 1,
    });
});

// ---- Pricing posters (51-56): offer, first month free, yearly plan; English and Bangla each ----
const PRICE = {
    en: {
        label: 'Launch offer', foot: 'Free demo available', per: 'BDT per month, per branch', bdt: 'BDT',
        regular: 'Regular price 12,000 BDT', off: '58% OFF', free: 'FREE',
        offerHead: 'Only 5,000 BDT per month per branch', offerSub: 'Launch offer for diagnostic centers and clinics. Regular price is 12,000 BDT.',
        pills: [['Installation', '25,000 BDT', 'one time'], ['1st month', 'FREE', 'after installation'], ['Yearly payment', '2 months FREE', 'pay 10, use 12']],
        startHead: 'Start now. First month is free.', startSub: 'Install once, go live, and pay the monthly fee only after your free first month.',
        steps: [['Install', '25,000 BDT', 'one time'], ['Month 1', 'FREE', 'after installation'], ['Month 2 onward', '5,000 BDT', 'per month, per branch']],
        yearHead: 'Pay 10 months. Use 12.', yearSub: 'Pay yearly and get 2 months free on every branch.',
        yearTotal: '50,000 BDT', yearNote: 'per branch for a full year', paid: 'paid', month: 'month',
    },
    bn: {
        label: 'লঞ্চ অফার', foot: 'ফ্রি ডেমো আছে', per: 'টাকা প্রতি মাসে, শাখাপ্রতি', bdt: 'টাকা',
        regular: 'নিয়মিত মূল্য ১২,০০০ টাকা', off: '৫৮% ছাড়', free: 'ফ্রি',
        offerHead: 'মাসে শাখাপ্রতি মাত্র ৫,০০০ টাকা', offerSub: 'ডায়াগনস্টিক সেন্টার ও ক্লিনিকের জন্য লঞ্চ অফার। নিয়মিত মূল্য ১২,০০০ টাকা।',
        pills: [['ইনস্টলেশন', '২৫,০০০ টাকা', 'এককালীন'], ['১ম মাস', 'ফ্রি', 'ইনস্টলেশনের পর'], ['বার্ষিক পেমেন্ট', '২ মাস ফ্রি', '১০ মাসের মূল্যে ১২ মাস']],
        startHead: 'এখনই শুরু করুন। ১ম মাস ফ্রি।', startSub: 'একবার ইনস্টল করুন, চালু করুন, আর মাসিক ফি দিন ফ্রি মাসের পর।',
        steps: [['ইনস্টল', '২৫,০০০ টাকা', 'এককালীন'], ['১ম মাস', 'ফ্রি', 'ইনস্টলেশনের পর'], ['২য় মাস থেকে', '৫,০০০ টাকা', 'প্রতি মাসে, শাখাপ্রতি']],
        yearHead: '১০ মাসের মূল্যে ১২ মাস।', yearSub: 'বার্ষিক পেমেন্টে প্রতি শাখায় ২ মাস ফ্রি।',
        yearTotal: '৫০,০০০ টাকা', yearNote: 'শাখাপ্রতি পুরো বছরের জন্য', paid: 'পেইড', month: 'মাস',
    },
};
const bnDigits = (n) => String(n).replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]);
const priceCss = `
.stage.price{background:linear-gradient(150deg,#0A1428 0%,#0B2D66 55%,#0B63CE 100%);color:#fff}
.stage.price .ring1{background:#E8590C;opacity:.22;right:-260px;top:-260px;width:700px;height:700px}
.stage.price .ring2{background:#38A8FF;opacity:.10}
.stage.price .chip{background:#E8590C;color:#fff}
.pc{position:absolute;left:72px;right:72px;top:176px;bottom:136px;display:flex;flex-direction:column}
.pc h1{font-size:62px;line-height:1.14;margin-bottom:14px}
.stage.bn .pc h1{font-size:60px;line-height:1.24}
.pc p.sub{font-size:29px;line-height:1.38;color:#C9DCF5;font-weight:500}
.hero{position:relative;margin-top:26px;background:rgba(255,255,255,.10);border:2px solid rgba(255,255,255,.22);border-radius:36px;padding:34px 44px 38px}
.hero .off{position:absolute;right:34px;top:-30px;background:#E8590C;border-radius:999px;padding:12px 30px;font-size:34px;font-weight:700;font-family:'Poppins','Hind Siliguri',sans-serif;box-shadow:0 10px 24px rgba(0,0,0,.35)}
.hero s{display:block;font-size:34px;color:#B7CBE8;text-decoration-color:#FF7A2E;text-decoration-thickness:4px;font-weight:600}
.hero .big{display:flex;align-items:flex-end;gap:22px;margin-top:2px}
.hero .big b{font-family:'Poppins',sans-serif;font-size:210px;line-height:1;font-weight:700;letter-spacing:-6px;color:#fff}
.stage.bn .hero .big b{font-family:'Hind Siliguri','Poppins',sans-serif;font-size:180px;letter-spacing:-2px}
.hero .big span{font-size:34px;line-height:1.3;font-weight:600;padding-bottom:30px;color:#fff}
.hero .big u{text-decoration:none;color:#FFB06B;font-size:44px;font-weight:700;display:block}
.pills{margin-top:auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.pill{border-radius:26px;padding:22px 24px;background:rgba(255,255,255,.12);border-left:10px solid #38A8FF}
.pill.g{border-left-color:#22C27A}.pill.o{border-left-color:#FF7A2E}
.pill small{display:block;font-size:22px;font-weight:600;color:#B7CBE8;letter-spacing:.3px}
.pill b{display:block;font-size:35px;line-height:1.15;margin:4px 0 2px;font-family:'Poppins','Hind Siliguri',sans-serif;font-weight:700}
.stage.bn .pill b{font-size:34px}
.pill i{font-style:normal;font-size:22px;color:#D5E4F8}
.tl{display:flex;flex-direction:column;gap:18px;margin-top:30px}
.tl .st{display:flex;align-items:center;gap:26px;border-radius:30px;padding:26px 32px;background:rgba(255,255,255,.12)}
.tl .st.g{background:#16915A}.tl .st.b{background:#0B63CE}
.tl .n{flex:none;width:76px;height:76px;border-radius:50%;background:#fff;color:#0A1428;font-size:40px;font-weight:700;display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif}
.tl .st small{display:block;font-size:26px;font-weight:600;color:#D5E4F8}
.tl .st b{font-size:50px;font-family:'Poppins','Hind Siliguri',sans-serif;line-height:1.15}
.stage.bn .tl .st b{font-size:46px}
.tl .st i{font-style:normal;font-size:26px;margin-left:auto;color:#EAF3FF;text-align:right}
.note{margin-top:auto;font-size:28px;color:#C9DCF5;font-weight:600}
.note s{text-decoration-color:#FF7A2E;text-decoration-thickness:3px}
.months{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-top:30px}
.months div{border-radius:22px;background:#0B63CE;padding:18px 0 14px;text-align:center}
.months div.f{background:#16915A;outline:4px solid #7CF0B7}
.months b{display:block;font-size:44px;font-family:'Poppins','Hind Siliguri',sans-serif;line-height:1.1}
.months i{display:block;font-style:normal;font-size:20px;color:#DCEBFF;margin-top:2px}
.total{margin-top:auto;display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.12);border-radius:30px;padding:26px 36px}
.total b{font-size:68px;font-family:'Poppins','Hind Siliguri',sans-serif;line-height:1.1}
.total span{font-size:28px;color:#D5E4F8;font-weight:600;text-align:right}
.total em{font-style:normal;background:#E8590C;border-radius:999px;padding:10px 26px;font-size:32px;font-weight:700;display:inline-block;margin-bottom:6px}
`;

function priceHtml(n, variant, lang) {
    const T = PRICE[lang];
    const n5 = lang === 'bn' ? '৫,০০০' : '5,000';
    let body = '';
    let head;
    let sub;
    if (variant === 'offer') {
        head = T.offerHead;
        sub = T.offerSub;
        body = `<div class="hero"><div class="off">${T.off}</div><s>${T.regular}</s><div class="big"><b>${n5}</b><span><u>${T.bdt}</u>${T.per.replace(T.bdt + ' ', '')}</span></div></div>
<div class="pills">${T.pills.map((p, i) => `<div class="pill ${i === 1 ? 'g' : i === 2 ? 'o' : ''}"><small>${p[0]}</small><b>${p[1]}</b><i>${p[2]}</i></div>`).join('')}</div>`;
    } else if (variant === 'start') {
        head = T.startHead;
        sub = T.startSub;
        body = `<div class="tl">${T.steps.map((s, i) => `<div class="st ${i === 1 ? 'g' : i === 2 ? 'b' : ''}"><div class="n">${lang === 'bn' ? bnDigits(i + 1) : i + 1}</div><div><small>${s[0]}</small><b>${s[1]}</b></div><i>${s[2]}</i></div>`).join('')}</div>
<div class="note"><s>${T.regular}</s> &rarr; ${n5} ${T.bdt}</div>`;
    } else {
        head = T.yearHead;
        sub = T.yearSub;
        body = `<div class="months">${Array.from({ length: 12 }, (_, m) => m >= 10
            ? `<div class="f"><b>${T.free}</b><i>${T.month} ${lang === 'bn' ? bnDigits(m + 1) : m + 1}</i></div>`
            : `<div><b>${lang === 'bn' ? bnDigits(m + 1) : m + 1}</b><i>${T.paid}</i></div>`).join('')}</div>
<div class="total"><div><em>${T.pills[2][1]}</em><br><b>${T.yearTotal}</b></div><span>${T.yearNote}<br>${n5} ${T.bdt} &times; ${lang === 'bn' ? '১০' : '10'}</span></div>`;
    }
    const theme = themes[1];
    return `<!doctype html><html lang="${lang}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>post-${n}</title><style>${css(theme)}${priceCss}</style></head><body>
<div class="stage price ${lang === 'bn' ? 'bn' : ''}" id="s"><div class="ring1"></div><div class="ring2"></div>
<div class="top"><div class="brand">${mark}<span>DT Medix</span></div><div class="chip">${T.label}</div></div>
<div class="pc"><h1>${head}</h1><p class="sub">${sub}</p>${body}</div>
<div class="foot"><span>${T.foot}</span><b>${PHONE}</b><span>${SITE}</span></div></div>
<script>function fit(){var s=document.getElementById('s'),k=Math.min(innerWidth,innerHeight)/1080;s.style.transform='scale('+k+')'}fit();addEventListener('resize',fit)</script>
</body></html>`;
}

[['offer', 'en'], ['offer', 'bn'], ['start', 'en'], ['start', 'bn'], ['year', 'en'], ['year', 'bn']].forEach(([variant, lang], k) => {
    const idx = P.length + k;
    const n = String(idx + 1).padStart(2, '0');
    writeFileSync(`post-${n}.html`, priceHtml(n, variant, lang));
    const en = PRICE.en;
    const bn = PRICE.bn;
    const key = { offer: ['offerHead', 'offerSub'], start: ['startHead', 'startSub'], year: ['yearHead', 'yearSub'] }[variant];
    const bullets = {
        offer: (L) => [`${L.regular}`, `${L.pills[0][0]}: ${L.pills[0][1]} (${L.pills[0][2]})`, `${L.pills[1][0]}: ${L.pills[1][1]} (${L.pills[1][2]})`, `${L.pills[2][0]}: ${L.pills[2][1]} (${L.pills[2][2]})`],
        start: (L) => L.steps.map((s) => `${s[0]}: ${s[1]} (${s[2]})`),
        year: (L) => [`${L.pills[2][1]} (${L.pills[2][2]})`, `${L.yearTotal} ${L.yearNote}`],
    }[variant];
    const tags = ['Pricing', 'LaunchOffer', 'DiagnosticCenter', 'DTMedix', 'DatatechBD'];
    const tagText = tags.map((x) => `#${x}`).join(' ');
    out.push({
        id: `post-${n}`, number: idx + 1, type: 'price', image: `images/post-${n}.jpg`, image_language: lang, icon: 'tag',
        headline_en: en[key[0]], subline_en: en[key[1]], bullets_en: bullets(en),
        headline_bn: bn[key[0]], subline_bn: bn[key[1]], bullets_bn: bullets(bn),
        caption_en: `${en[key[0]]} ${en[key[1]]}\n\n${bullets(en).map((x) => `- ${x}`).join('\n')}\n\nTry the free demo: dt-medix.datatechbd.com or call ${PHONE}. ${SITE}\n\n${tagText}`,
        caption_bn: `${bn[key[0]]} ${bn[key[1]]}\n\n${bullets(bn).map((x) => `- ${x}`).join('\n')}\n\nফ্রি ডেমো: dt-medix.datatechbd.com অথবা কল করুন ${PHONE}। ${SITE}\n\n${tagText}`,
        hashtags: tags,
        call_to_action: { label_en: 'Try the free demo', label_bn: 'ফ্রি ডেমো চালিয়ে দেখুন', phone: PHONE, website: SITE },
        alt_text: `${en[key[0]]} - DT Medix pricing, a product of Datatech BD Ltd.`,
        suggested_schedule_day: (idx % 7) + 1,
    });
});

writeFileSync('../posts.json', JSON.stringify({ brand: 'DT Medix', publisher: 'Datatech BD Ltd.', version: 'v1', image_size: '1080x1080', count: out.length, posts: out }, null, 2));
console.log('built', out.length);
