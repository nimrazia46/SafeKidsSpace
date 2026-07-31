-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2026 at 10:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `safekidsspace`
--

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `badge_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `title`, `badge_image`, `description`) VALUES
(1, 'Math Champion', NULL, 'Completed all maths quizzes'),
(2, 'Coding Star', NULL, 'Completed coding challenges'),
(3, 'English Hero', NULL, 'Excellent English performance');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `section` varchar(50) DEFAULT 'Trending',
  `pdf_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `img_url`, `section`, `pdf_url`) VALUES
(1, 'Zac The Rat', 'by Starfall', 'rat.png', 'Trending', '1-Zac-by-Starfall.pdf'),
(2, 'Peg The Hen', 'by Starfall', 'peg.png', 'New', '2-Peg-by-Starfall.pdf'),
(3, 'Reach The Stars', 'by Stephen Schutz', 'stars.png', 'Staff Picks', 'ReachForTheStars_by_Starfall.pdf'),
(4, 'Lets Eat', 'by Starfall', 'eat.png', 'Featured', 'SB1544_LetsEat_web.pdf'),
(5, 'Who Likes The Rain', 'by Starfall', 'rain.png', 'Trending', 'SB1384_WhoLikesTheRain_byStarfall.pdf'),
(6, 'Helen Keler', 'by Starfall', 'hellen.png', 'Trending', 'SB2404_HelenKeller_byStarfall.pdf'),
(7, 'Dear Dragon', 'by Starfall', 'deardragon.png', 'Featured', 'MothersDayD-by-Starfall.pdf'),
(8, 'Sky Ride', 'by Starfall', 'skyride.png', 'New', 'li-skyride.pdf'),
(9, 'How The Trutle Cracked Its Shell', 'by Annette Frei', 'turtle.png', 'Trending', 'HowTheTurtleCracked_by_Starfall'),
(10, 'Dune Buggy', 'by Starfall', 'buggy.png', 'Trending', '10-DuneBuggy-by-Starfall.pdf'),
(11, 'Two Little Engines', 'by Marc Buchanan', 'engine.png', 'Trending', 'SB1438_TwoLittleEngines_byStarfall.pdf'),
(12, 'Backpack Bear Math\'s Book', 'by Starfall', 'math.png', 'New', 'MB2206_BpBsMathBook_byStarfall.pdf'),
(13, 'The No-Tail Cat', 'by Margaret Hillert', 'cat.png', 'New', 'NoTailCat-4Teach_full-sm.pdf'),
(14, 'Young Hero', 'By Chase Tunbridge', 'hero.png', 'Featured', 'SB820_YoungHero_web.pdf'),
(15, 'The Surfer Girl', 'by Starfall', 'surfur.png', 'New', '14-SurferGirl-by-Starfall.pdf'),
(16, 'The Ant And The Chrysalis', ' by Myrna Estes', 'ant.png', 'Trending', 'SB1537_TheAntAndTheCrysalis_byStarfall.pdf'),
(17, 'Plant Book', 'Written by Alice O. Shepard', 'plants.png', 'Staff Picks', 'SB776_backpack-bears-plant-book.pdf'),
(18, 'Over In The Meadow', 'by Olive A. Wadsworth', 'meadow.png', 'New', 'SB1551_OverInTheMeadow_byStarfall.pdf'),
(19, 'Green Grass Grew', 'by William Jerome ', 'grass.png', 'Trending', 'SB1513_GreenGrassGrew_byStarfall.pdf'),
(20, 'Cobler And The Elves', ' by Brandi Chase ', 'cobbler.png', 'Trending', 'SB1353_CobblerAndTheElves_byStarfall.pdf'),
(21, 'Bird Book', ' Written by Alice O. Shepard', 'birds.png', 'Featured', 'SB875_backpack-bears-bird-book.pdf'),
(22, 'Invertibrates Book', 'Written by Alice O. Shepard', 'inverti.png', 'Featured', 'SB899_backpack-bears-invertebrates-book.pdf'),
(23, 'Mom What Is Diversity', 'by T.Albert', 'diversitity.png', 'Trending', '018-HEY-MOM-WHAT-IS-DIVERSITY-Free-Childrens-Book-By-Monkey-Pen (1).pdf'),
(24, 'Mix It Up', 'by T.Albert', 'mix.png', 'Staff Picks', '016-MIX-IT-UP-Free-Childrens-Book-By-Monkey-Pen.pdf'),
(25, 'In The Deep', 'by Bel Richardson', 'deep.png', 'New', 'in-the-deep.pdf'),
(26, 'Where Is My Shoes', 'by Ounla Santi', 'shoe.png', 'Featured', 'where-is-my-shoe-6.pdf'),
(27, 'Sunny Meadow Woodland', 'by T.Albert', 'sunny.png', 'Featured', '005-SUNNY-MEADOWS-WOODLAND-SCHOOL-Free-Childrens-Book-By-Monkey-Pen.pdf'),
(28, 'Girl Of The Forest', 'by Seat Sopheap', 'forest.png', 'New', 'girl-of-the-forest.pdf'),
(29, 'A Small Boat In A Big Lake', 'by Singgih Cahyo Jadmiko', 'boat.png', 'New', 'a-small-boat-in-a-big-lake-c.pdf'),
(30, 'Flower Hunt', 'by Binita', 'flower.png', 'New', 'flower-hunt.pdf'),
(31, 'The Ocean World', 'by Li Thi Bich Koa', 'ocean.png', 'Featured', 'the-ocean-world.pdf'),
(32, 'Litter Bug', 'Written By Bel Richardson', 'litter.png', 'Staff Picks', 'litterbug-d.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `career_applications`
--

CREATE TABLE `career_applications` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `program_title` varchar(150) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `emergency_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `education_level` varchar(100) DEFAULT NULL,
  `institution` varchar(150) DEFAULT NULL,
  `subjects_taught` varchar(255) DEFAULT NULL,
  `experience_level` enum('fresh','1-2','3-5','5+') DEFAULT NULL,
  `preferred_mode` enum('online','on-campus','both') DEFAULT NULL,
  `why_teach` text DEFAULT NULL,
  `cgpa` varchar(20) DEFAULT NULL,
  `program_of_study` varchar(150) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `certification` varchar(255) DEFAULT NULL,
  `edu_start_date` date DEFAULT NULL,
  `edu_end_date` date DEFAULT NULL,
  `edu_in_process` tinyint(1) NOT NULL DEFAULT 0,
  `is_fresher` tinyint(1) NOT NULL DEFAULT 0,
  `industry_type` varchar(150) DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `exp_start_date` date DEFAULT NULL,
  `exp_end_date` date DEFAULT NULL,
  `exp_currently_working` tinyint(1) NOT NULL DEFAULT 0,
  `cv_path` varchar(255) NOT NULL,
  `status` enum('pending','shortlisted','rejected','hired') NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `career_applications`
--

INSERT INTO `career_applications` (`id`, `program_id`, `program_title`, `full_name`, `email`, `mobile_number`, `cnic`, `dob`, `gender`, `emergency_number`, `address`, `education_level`, `institution`, `subjects_taught`, `experience_level`, `preferred_mode`, `why_teach`, `cgpa`, `program_of_study`, `specialization`, `certification`, `edu_start_date`, `edu_end_date`, `edu_in_process`, `is_fresher`, `industry_type`, `designation`, `organization`, `exp_start_date`, `exp_end_date`, `exp_currently_working`, `cv_path`, `status`, `applied_at`) VALUES
(1, 1, 'Little Explorers', 'nimra', 'nimrazia@gmail.com', '0312-4567890', NULL, NULL, NULL, NULL, NULL, 'bachlors', 'university of Karachi', 'maths science 3to7', '1-2', 'online', '', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, 0, 'career/uploads/cv/cv_nimra_1784392182_4f2203c4.pdf', 'pending', '2026-07-18 16:29:42'),
(2, 1, 'Little Explorers', 'nimra', 'nimrazia46@gmail.com', '0312-4567890', NULL, NULL, NULL, NULL, NULL, 'wf', 'gaeagggggggg', '5to6', 'fresh', 'online', 'ageeeeed', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, 0, 'career/uploads/cv/cv_nimra_1784746440_d6b9457d.pdf', 'shortlisted', '2026-07-22 18:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `certificate_code` varchar(100) DEFAULT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot`
--

CREATE TABLE `chatbot` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot`
--

INSERT INTO `chatbot` (`id`, `keyword`, `response`, `status`) VALUES
(1, 'about safekidsspace', 'SafeKidsSpace is a secure educational platform designed for children to learn through quizzes, games, coding, grammar, geography, and other fun activities.', 1),
(2, 'technical help', 'If you are experiencing technical issues, try refreshing the page, clearing your browser cache, or using the latest version of Chrome, Firefox, or Edge.', 1),
(3, 'contact support', 'You can contact our support team at support@safekidsspace.com or use the Contact Us page.', 1),
(4, 'quiz', 'Choose a quiz from the library and click Start Quiz to begin learning.', 1),
(5, 'coding', 'The Coding section helps children learn programming concepts through interactive challenges.', 1),
(6, 'grammar', 'The Grammar section contains fun exercises to improve English grammar skills.', 1),
(7, 'geography', 'The Geography section helps students learn about countries, capitals, flags, and maps.', 1),
(8, 'science', 'The Science section includes engaging quizzes and activities about space, nature, animals, and more.', 1),
(9, 'math', 'Practice addition, subtraction, multiplication, division, and other math topics in the Math section.', 1),
(10, 'profile', 'You can update your profile information from the Profile page after logging in.', 1),
(11, 'login', 'Use your registered email and password to log in to your account.', 1),
(12, 'register', 'Create a new account by clicking the Register button on the homepage.', 1),
(13, 'leaderboard', 'The leaderboard displays the top-performing students based on quiz scores.', 1),
(14, 'badges', 'Earn badges by completing quizzes, maintaining streaks, and achieving high scores.', 1),
(15, 'hello', 'Hello! 👋 I am CosmoBot. How can I help you today?', 1),
(16, 'hi', 'Hi! 😊 Ask me anything about SafeKidsSpace.', 1),
(17, 'thank you', 'You are welcome! Happy learning! 🚀', 1),
(18, 'bye', 'Goodbye! Have a wonderful day and keep learning! 🌟', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `difficulty` enum('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `thumbnail`, `category`, `difficulty`, `teacher_id`, `created_at`) VALUES
(1, 'Kids Coding Basics', 'Learn HTML CSS and JavaScript', NULL, 'Coding', 'Beginner', NULL, '2026-06-04 17:57:02'),
(2, 'Fun Mathematics', 'Interactive maths learning for kids', NULL, 'Math', 'Beginner', NULL, '2026-06-04 17:57:02'),
(3, 'English Speaking', 'Improve spoken English skills', NULL, 'English', 'Intermediate', NULL, '2026-06-04 17:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `task_instruction` text NOT NULL,
  `xp_reward` int(11) DEFAULT 15,
  `task_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deactivation_requests`
--

CREATE TABLE `deactivation_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `status` enum('trial','active','expired') NOT NULL DEFAULT 'trial',
  `free_video_used` tinyint(1) NOT NULL DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `parent_id`, `child_id`, `program_id`, `status`, `free_video_used`, `started_at`, `expires_at`) VALUES
(1, 15, 19, 1, 'active', 0, '2026-07-03 21:42:45', '2026-08-09'),
(2, 15, 19, 2, 'active', 0, '2026-07-13 22:13:16', '2026-08-14'),
(3, 15, 19, 3, 'active', 0, '2026-07-18 21:53:13', '2026-08-21'),
(4, 15, 19, 4, 'trial', 0, '2026-07-20 22:51:47', NULL),
(5, 15, 28, 1, 'trial', 0, '2026-07-21 08:56:43', NULL),
(6, 15, 28, 2, 'trial', 0, '2026-07-21 09:26:25', NULL),
(7, 15, 28, 3, 'trial', 0, '2026-07-21 11:04:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `question` varchar(255) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `category_id`, `question`, `answer`, `status`) VALUES
(31, 3, 'How do I update my profile?', 'You can update your profile picture anytime from your avatar in the top navigation. Other profile details are currently managed by SafeKidsSpace support.', 1),
(32, 3, 'How can I change my password?', 'Click your profile icon in the top navigation, open Account Settings, and use the Change Password form (current password + new password required).', 1),
(33, 3, 'Can I change my profile picture?', 'Yes. Click your profile picture in the top navigation and choose a new photo — it updates instantly.', 1),
(34, 3, 'How do I change my username?', 'Your username is generated from your email and can\'t be changed directly. For a child account, the username is set by the parent when the account is created; contact support for changes.', 1),
(35, 3, 'How do I delete my account?', 'From Account Settings you can request Account Deactivation (needs admin approval, and you can keep using your account until then). For a full, permanent account deletion, please contact our support team.', 1),
(36, 3, 'Can I update my email address?', 'Email addresses can\'t be changed from your account settings yet — please contact our support team and they\'ll update it for you.', 1),
(37, 3, 'How can I view my learning history?', 'Visit the Learning page to see your enrolled programs, quiz results, and overall progress.', 1),
(38, 3, 'Can I use my account on multiple devices?', 'Yes. Simply log in with the same account on any supported device.', 1),
(39, 4, 'How do I start a quiz?', 'Open any quiz category and click the Start Quiz button.', 1),
(40, 4, 'Can I retake a quiz?', 'Yes, you can retake quizzes as many times as you like.', 1),
(41, 4, 'Are my quiz scores saved?', 'Yes, your scores are automatically saved after each completed quiz.', 1),
(42, 4, 'What happens if I answer incorrectly?', 'The correct answer will be shown so you can learn from your mistakes.', 1),
(43, 4, 'Do quizzes have a timer?', 'Some quizzes include a timer depending on the quiz type.', 1),
(44, 4, 'Can I pause a quiz?', 'Some quizzes support pausing while others must be completed in one session.', 1),
(45, 4, 'How do I earn more points?', 'Answer questions correctly and complete quizzes regularly.', 1),
(46, 4, 'Are there different difficulty levels?', 'Yes, some quizzes are available in Easy, Medium, and Hard levels.', 1),
(47, 5, 'Is my personal information safe?', 'Yes. We use secure systems to protect your personal information.', 1),
(48, 5, 'Do you share my information with others?', 'No. We never sell or share your personal information with third parties.', 1),
(49, 5, 'Is SafeKidsSpace safe for children?', 'Yes. All educational content is reviewed before publication.', 1),
(50, 5, 'Can parents monitor their children?', 'Yes. Parents can monitor learning progress and achievements.', 1),
(51, 5, 'How is my data protected?', 'Your data is stored securely using encryption and security best practices.', 1),
(52, 5, 'Are advertisements shown to children?', 'No. SafeKidsSpace provides an ad-free learning experience.', 1),
(53, 5, 'Can I report inappropriate content?', 'Yes. Use the Report option or contact our support team.', 1),
(54, 5, 'Why do I need to log in?', 'Logging in helps save your progress and keeps your account secure.', 1),
(55, 6, 'How do I earn badges?', 'Complete quizzes and reach score milestones to earn badges.', 1),
(56, 6, 'What is the leaderboard?', 'The leaderboard ranks students based on their quiz performance.', 1),
(57, 6, 'How do I increase my score?', 'Practice regularly and answer quiz questions correctly.', 1),
(58, 6, 'What are achievement points?', 'Achievement points are earned by completing quizzes and activities.', 1),
(59, 6, 'Can I unlock special rewards?', 'Yes. Some achievements unlock special badges and rewards.', 1),
(60, 6, 'How do learning streaks work?', 'Complete quizzes daily to maintain your learning streak.', 1),
(61, 6, 'Do badges expire?', 'No. Once earned, badges remain permanently in your profile.', 1),
(62, 6, 'Can I compare my achievements with friends?', 'Yes, you can compare scores and badges on the leaderboard.', 1),
(63, 7, 'What can I buy in the SafeKidsSpace store?', 'Our store offers learning kits, educational toys, books, and creative activity sets designed for kids.', 1),
(64, 7, 'How do I place an order?', 'Browse the Store, add items to your cart, and click Checkout to complete your purchase securely.', 1),
(65, 7, 'How can I track my order?', 'Go to My Orders in your account to see the status of every order you have placed.', 1),
(66, 7, 'What payment methods are accepted?', 'We accept the payment options shown at checkout; details are provided on the payment form during purchase.', 1),
(67, 7, 'Can I cancel or return a product?', 'Please contact our support team as soon as possible after ordering to request a cancellation or return.', 1),
(68, 7, 'Are store products safe for children?', 'Yes, every product listed in our store is reviewed to ensure it is age-appropriate and safe for kids.', 1),
(69, 7, 'Who can place an order, parents or kids?', 'Orders are typically placed by a parent or guardian account to keep checkout and payment secure.', 1),
(70, 8, 'What games are available on SafeKidsSpace?', 'We offer a Word Search game, Trace the Alphabet, Math Match, and an Arcade with fun mini-games.', 1),
(71, 8, 'How do I play the Word Search game?', 'Open Games, choose Word Search, and find the hidden words in the letter grid before time runs out.', 1),
(72, 8, 'What is the Trace the Alphabet game?', 'It helps kids practice handwriting by tracing letters on screen with fun guided animations.', 1),
(73, 8, 'Is my game progress saved?', 'Yes, your progress and scores are automatically saved to your account after every game session.', 1),
(74, 8, 'Can I play games without logging in?', 'You can preview some activities, but logging in is required to save scores and unlock full features.', 1),
(75, 8, 'How does the Arcade work?', 'The Arcade offers quick mini-games with difficulty levels like Easy and Hard, plus a performance rating.', 1),
(76, 8, 'Are the games educational?', 'Yes, every game is designed to build a specific skill such as vocabulary, math, or handwriting.', 1),
(77, 9, 'What is available in the Library section?', 'The Library has a collection of magical storybooks and educational PDFs kids can read online.', 1),
(78, 9, 'Can I download the books?', 'Books in the library are meant to be read on-site; check each book page for download availability.', 1),
(79, 9, 'Can I save a book to read later?', 'Yes, use the save option on a book to bookmark it and continue reading later from your library.', 1),
(80, 9, 'Are the books age-appropriate?', 'Yes, all books are carefully selected and reviewed to be suitable and safe for children.', 1),
(81, 9, 'How often are new books added?', 'New stories and educational books are added regularly to keep the library fresh and exciting.', 1),
(84, 10, 'What kind of videos are on SafeKidsSpace?', 'We offer fun, educational videos covering topics like science, math, reading, and creativity.', 1),
(85, 10, 'What are Live Interactive Camps?', 'Live Interactive Camps are scheduled live classes where kids can learn and interact with teachers in real time.', 1),
(86, 10, 'How do I join a live class?', 'Go to your Programs or Dashboard, find the scheduled live class, and click Join at the class time.', 1),
(87, 10, 'Can I rewatch a video later?', 'Yes, uploaded videos remain available in the Videos section so you can watch them again anytime.', 1),
(88, 10, 'Are live classes recorded?', 'Some live classes may be recorded and added to your program videos for later viewing.', 1),
(91, 11, 'What are Learning Programs?', 'Learning Programs are structured courses combining videos, quizzes, and activities around a specific topic.', 1),
(92, 11, 'How do I enroll in a program?', 'Visit the Learning page, choose a program, and follow the enrollment steps to get started.', 1),
(93, 11, 'Can I see my enrollment status?', 'Yes, your enrollment and progress status for each program is shown on your Dashboard.', 1),
(94, 11, 'What platform features support learning?', 'SafeKidsSpace offers gamified learning, a digital library, smart quizzes, videos, and live classes together.', 1),
(95, 11, 'Who teaches the programs?', 'Programs are created and delivered by verified teachers who apply through our Careers page.', 1),
(98, 12, 'How can I create a child account?', 'From the Parent Dashboard, add a child profile with a username so they can log in separately and safely.', 1),
(99, 12, 'Can I monitor my child\'s activity?', 'Yes, the Parent Dashboard shows your child\'s learning activity, quiz results, and progress.', 1),
(100, 12, 'Can I see my child\'s orders and payments?', 'Yes, parents can view all orders and payment history linked to their account from the dashboard.', 1),
(101, 12, 'Can I manage which programs my child joins?', 'Yes, parents can view and manage program enrollments for their child from the Parent Dashboard.', 1),
(102, 12, 'Is the Parent Dashboard separate from my child\'s account?', 'Yes, parents and children have separate logins, and the parent account oversees the child account.', 1),
(105, 13, 'How do I apply to teach on SafeKidsSpace?', 'Visit the Careers page, fill out the Teacher Application form, and upload your CV to apply.', 1),
(106, 13, 'What happens after I submit a teacher application?', 'Our team reviews your application and will contact you regarding the next steps.', 1),
(107, 13, 'What can teachers do on the platform?', 'Approved teachers can manage live classes, program videos, quizzes, and view quiz results from their dashboard.', 1),
(108, 13, 'Do I need experience to apply as a teacher?', 'Requirements vary by role; check the specific listing on the Careers page for details.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `faq_categories`
--

CREATE TABLE `faq_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faq_categories`
--

INSERT INTO `faq_categories` (`id`, `category_name`, `icon`, `display_order`) VALUES
(3, 'Account & Profile', 'fa-user-astronaut', 1),
(4, 'Learning & Quizzes', 'fa-graduation-cap', 2),
(5, 'Safety & Privacy', 'fa-shield-halved', 3),
(6, 'Achievements & Badges', 'fa-trophy', 4),
(7, 'Store & Shopping', 'fa-cart-shopping', 5),
(8, 'Games & Activities', 'fa-gamepad', 6),
(9, 'Digital Library', 'fa-book-open', 7),
(10, 'Videos & Live Classes', 'fa-video', 8),
(11, 'Learning Programs', 'fa-chalkboard-user', 9),
(12, 'Parent Dashboard', 'fa-people-roof', 10),
(13, 'Careers & Teaching', 'fa-briefcase', 11);

-- --------------------------------------------------------

--
-- Table structure for table `game_scores`
--

CREATE TABLE `game_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `game_name` varchar(100) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `level` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homework_assignments`
--

CREATE TABLE `homework_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_reward` int(11) DEFAULT 50,
  `due_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kid_activity_logs`
--

CREATE TABLE `kid_activity_logs` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `duration_minutes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kid_activity_logs`
--

INSERT INTO `kid_activity_logs` (`id`, `child_id`, `activity_name`, `activity_type`, `points_earned`, `duration_minutes`, `created_at`) VALUES
(1, 19, 'Enrolled in: English Speaking', 'enrollment', 10, 0, '2026-06-27 21:19:33'),
(2, 19, 'Enrolled in: Fun Mathematics', 'enrollment', 10, 0, '2026-06-27 21:19:41'),
(3, 19, 'Enrolled in: Kids Coding Basics', 'enrollment', 10, 0, '2026-06-27 21:19:45'),
(4, 19, 'Watched video: ABC Song | Learn ABC Alphabet for Children (Little Explorers)', 'video_watch', 5, 0, '2026-07-05 20:47:23'),
(5, 19, 'Watched video: ABC Song | Learn ABC Alphabet for Children (Little Explorers)', 'video_watch', 5, 0, '2026-07-08 21:39:29'),
(6, 19, 'Watched video: Learn to write numbers 1-10 (Little Explorers)', 'video_watch', 5, 0, '2026-07-08 21:40:28'),
(7, 19, 'Watched video: ABC Song | Learn ABC Alphabet for Children (Little Explorers)', 'video_watch', 5, 0, '2026-07-10 21:55:26'),
(8, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:02:47'),
(9, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:02:51'),
(10, 19, 'Watched video: DIY 8 Paper Wonders! (Video Library)', 'video', 5, 0, '2026-07-11 13:08:25'),
(11, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:08:36'),
(12, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:08:38'),
(13, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:08:40'),
(14, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:08:42'),
(15, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:16:54'),
(16, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:25:10'),
(17, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:25:14'),
(18, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:26:48'),
(19, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:26:55'),
(20, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:30:22'),
(21, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:31:06'),
(22, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:36:31'),
(23, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:36:36'),
(24, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:36:42'),
(25, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-11 13:36:45'),
(26, 19, 'Completed quiz: Little Explorers Fun Quiz (5/6)', 'quiz', 10, 0, '2026-07-12 12:04:42'),
(27, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-12 12:15:32'),
(28, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-12 12:15:37'),
(29, 19, 'Enrolled in: Future Scientists', 'enrollment', 10, 0, '2026-07-13 22:13:16'),
(30, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-15 07:56:40'),
(31, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-15 20:56:53'),
(32, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-15 21:29:24'),
(33, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-17 20:19:16'),
(34, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 08:12:58'),
(35, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 08:38:14'),
(36, 19, 'Watched video: How Your Brain Works? (Program Video — Future Scientists)', 'video_watch', 5, 0, '2026-07-18 08:43:43'),
(37, 19, 'Watched video: EASY SCIENCE EXPERIMENTS (Program Video — Future Scientists)', 'video_watch', 5, 0, '2026-07-18 08:44:04'),
(38, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 19:29:59'),
(39, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 19:30:04'),
(40, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 21:46:15'),
(41, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 21:47:54'),
(42, 19, 'Enrolled in: Young Coders', 'enrollment', 10, 0, '2026-07-18 21:53:13'),
(43, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-18 21:57:58'),
(44, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-19 15:16:21'),
(45, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-19 15:17:22'),
(46, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-19 19:58:57'),
(47, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-19 19:59:01'),
(48, 19, 'Watched video: Science Videos For Kids (Video Library)', 'video', 5, 0, '2026-07-19 20:53:52'),
(49, 19, 'Watched video: Learn Colors, ABCs & Fruits for Toddlers (Video Library)', 'video', 5, 0, '2026-07-19 20:53:56'),
(50, 19, 'Enrolled in: AI & Tech Innovators Lab', 'enrollment', 10, 0, '2026-07-20 22:51:47'),
(51, 28, 'Enrolled in: Little Explorers', 'enrollment', 10, 0, '2026-07-21 08:56:43'),
(52, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:18'),
(53, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:20'),
(54, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:22'),
(55, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:23'),
(56, 19, 'Watched video: THE FOX AND THE CROW (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:26'),
(57, 19, 'Watched video: Kindergarten | Learn Basic (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:27'),
(58, 19, 'Watched video: Story for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:28'),
(59, 19, 'Watched video: Story for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:13:58'),
(60, 19, 'Watched video: Story for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:00'),
(61, 19, 'Watched video: Kindergarten | Learn Basic (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:01'),
(62, 19, 'Watched video: THE FOX AND THE CROW (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:02'),
(63, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:02'),
(64, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:03'),
(65, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:05'),
(66, 19, 'Watched video: ABC Phonics Chant for Children | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 19:14:06'),
(67, 19, 'Watched video: ABC Phonics | Sounds and Actions from A to Z (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:40'),
(68, 19, 'Watched video: Learn to write numbers 1-10 (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:50'),
(69, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:51'),
(70, 19, 'Watched video: How Many Fingers | Kids Song | Action Songs for Children (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:53'),
(71, 19, 'Watched video: THE FOX AND THE CROW (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:55'),
(72, 19, 'Watched video: Colors Song for Preschool & Kindergarten | Learn Basic Colors (Program Video — Little Explorers)', 'video_watch', 5, 0, '2026-07-21 22:34:57'),
(73, 19, 'Watched video: Science Videos For Kids With Blippi (Video Library)', 'video', 5, 0, '2026-07-21 22:35:06'),
(74, 19, 'Watched video: Coding for Kids Explained (Video Library)', 'video', 5, 0, '2026-07-21 22:35:11'),
(75, 19, 'Watched video: Introduction to Coding (Video Library)', 'video', 5, 0, '2026-07-21 22:35:12'),
(76, 19, 'Watched video: Learn Parts of Body Names (Video Library)', 'video', 5, 0, '2026-07-21 22:35:12'),
(77, 19, 'Watched video: Science Videos For Kids With Blippi (Video Library)', 'video', 5, 0, '2026-07-21 22:35:13'),
(78, 19, 'Watched video: Science Videos For Kids With Blippi (Video Library)', 'video', 5, 0, '2026-07-22 22:40:27'),
(79, 17, 'Fun Quiz (Iq) — scored 35', 'quiz', 35, 0, '2026-07-28 19:59:34'),
(80, 17, 'Fun Quiz (Iq) — scored 50', 'quiz', 50, 0, '2026-07-28 20:04:19'),
(81, 17, 'Fun Quiz (Iq) — scored 50', 'quiz', 50, 0, '2026-07-28 20:04:37'),
(82, 19, 'Fun Quiz (Iq) — scored 60', 'quiz', 60, 0, '2026-07-28 20:38:25'),
(83, 19, 'Completed quiz: Little Explorers Fun Quiz 2 (3/10)', 'quiz', 6, 0, '2026-07-28 21:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `leaderboard`
--

CREATE TABLE `leaderboard` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `score` int(11) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'iq',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaderboard`
--

INSERT INTO `leaderboard` (`id`, `username`, `user_id`, `score`, `category`, `created_at`) VALUES
(10, 'Admin User', 17, 35, 'iq', '2026-07-28 19:59:34'),
(11, 'Admin User', 17, 50, 'iq', '2026-07-28 20:04:19'),
(12, 'Admin User', 17, 50, 'iq', '2026-07-28 20:04:37'),
(13, 'arwa', 19, 60, 'iq', '2026-07-28 20:38:25');

-- --------------------------------------------------------

--
-- Table structure for table `live_classes`
--

CREATE TABLE `live_classes` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_title` varchar(255) NOT NULL,
  `subject_tag` varchar(100) NOT NULL,
  `meeting_link` text NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_class_waitlist`
--

CREATE TABLE `live_class_waitlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `live_class_waitlist`
--

INSERT INTO `live_class_waitlist` (`id`, `user_id`, `joined_at`) VALUES
(1, 17, '2026-07-02 20:30:24'),
(2, 19, '2026-07-02 22:30:16');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `target_role` varchar(20) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-solid fa-bell',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `target_role`, `title`, `message`, `link`, `icon`, `is_read`, `created_at`) VALUES
(1, NULL, NULL, 'Welcome to SafeKidsSpace!', 'Explore videos, quizzes, and the digital library to get started.', 'index.php', 'fa-solid fa-rocket', 1, '2026-07-08 09:16:02'),
(2, NULL, NULL, 'New Quiz Open', 'A fresh space mission has opened inside your active lessons tab.', 'learning.php', 'fa-solid fa-graduation-cap', 1, '2026-07-08 09:16:02'),
(3, NULL, NULL, 'Store Items Updated', 'Check out the newly stocked space gear rewards!', 'store.php', 'fa-solid fa-ticket', 1, '2026-07-08 09:16:02'),
(4, NULL, 'admin', 'New video submitted', 'zia submitted \"EASY SCIENCE EXPERIMENTS\" for review.', 'admin_program_videos.php', 'fa-solid fa-clapperboard', 1, '2026-07-13 00:35:47'),
(5, 18, NULL, 'Video approved', 'Your video \"EASY SCIENCE EXPERIMENTS\" was approved and is now live.', 'teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-13 00:36:22'),
(6, NULL, 'admin', 'New video submitted', 'zia submitted \"How Your Brain Works?\" for review.', 'admin_program_videos.php', 'fa-solid fa-clapperboard', 1, '2026-07-13 00:38:56'),
(7, NULL, 'admin', 'New program enrollment', 'nimra enrolled arwa in \"Future Scientists\".', 'admin_dashboard.php', 'fa-solid fa-graduation-cap', 0, '2026-07-13 22:13:16'),
(8, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.1,499 for Future Scientists (arwa).', 'admin_payments.php', 'fa-solid fa-money-check-dollar', 1, '2026-07-13 22:13:25'),
(9, NULL, 'admin', 'New order placed', 'arwa placed a new order (#3) worth Rs.850.', 'admin_orders.php', 'fa-solid fa-cart-shopping', 0, '2026-07-14 20:55:13'),
(10, NULL, 'admin', 'New order placed', 'arwa placed a new order (#4) worth Rs.2,350.', 'admin_orders.php', 'fa-solid fa-cart-shopping', 0, '2026-07-15 21:06:18'),
(11, 18, NULL, 'Video approved', 'Your video \"How Your Brain Works?\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-18 08:43:21'),
(12, 19, NULL, 'New video available!', 'A new video was just added to \"Future Scientists\": How Your Brain Works?', 'learning.php', 'fa-solid fa-clapperboard', 1, '2026-07-18 08:43:21'),
(13, NULL, 'admin', 'New teacher application', 'nimra applied for \"Little Explorers\".', 'admin/admin_career_applications.php', 'fa-solid fa-chalkboard-user', 1, '2026-07-18 16:29:42'),
(14, 19, NULL, 'Order confirmed', 'Your order #4 has been confirmed and is being processed.', 'store/my_orders.php', 'fa-solid fa-circle-check', 1, '2026-07-18 19:04:42'),
(15, 19, NULL, 'Order confirmed', 'Your order #3 has been confirmed and is being processed.', 'store/my_orders.php', 'fa-solid fa-circle-check', 1, '2026-07-18 20:57:19'),
(16, 19, NULL, 'Order delivered', 'Your order #3 has been delivered. We hope your kids enjoy it!', 'store/my_orders.php', 'fa-solid fa-truck', 1, '2026-07-18 20:57:43'),
(17, NULL, 'admin', 'New program enrollment', 'nimra enrolled arwa in \"Young Coders\".', 'admin/admin_dashboard.php', 'fa-solid fa-graduation-cap', 0, '2026-07-18 21:53:13'),
(18, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.1,999 for Young Coders (arwa).', 'admin/admin_payments.php', 'fa-solid fa-money-check-dollar', 0, '2026-07-20 22:38:14'),
(19, NULL, 'admin', 'New program enrollment', 'nimra enrolled arwa in \"AI & Tech Innovators Lab\".', 'admin/admin_dashboard.php', 'fa-solid fa-graduation-cap', 0, '2026-07-20 22:51:47'),
(20, NULL, 'admin', 'New program enrollment', 'nimra enrolled sam in \"Little Explorers\".', 'admin/admin_dashboard.php', 'fa-solid fa-graduation-cap', 0, '2026-07-21 08:56:43'),
(21, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.1,999 for Young Coders (arwa).', 'admin/admin_payments.php', 'fa-solid fa-money-check-dollar', 0, '2026-07-21 09:45:57'),
(22, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.1,999 for Young Coders (arwa).', 'admin/admin_payments.php', 'fa-solid fa-money-check-dollar', 0, '2026-07-21 09:45:57'),
(23, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.999 for Little Explorers (sam).', 'admin/admin_payments.php', 'fa-solid fa-money-check-dollar', 1, '2026-07-21 11:02:21'),
(24, NULL, 'student', '🎬 New video!', '\"dvaa\" just got added to Videos!', 'videos.php', 'fa-solid fa-video', 1, '2026-07-21 12:27:55'),
(25, NULL, 'student', '🎬 New video!', '\"Science Videos For Kids With Blippi\" just got added to Videos!', 'videos.php', 'fa-solid fa-video', 1, '2026-07-21 13:56:40'),
(26, NULL, 'admin', 'New payment submitted', 'nimra submitted Rs.1,499 for Future Scientists (sam).', 'admin/admin_payments.php', 'fa-solid fa-money-check-dollar', 0, '2026-07-21 15:41:46'),
(27, 18, NULL, 'Program assigned to you', 'You\'ve been assigned to manage a learning program. You can now add videos and quizzes to it.', 'teacher/teacher_program_videos.php', 'fa-solid fa-graduation-cap', 0, '2026-07-21 18:50:39'),
(28, NULL, 'admin', 'New video submitted', 'zia submitted \"THE FOX AND THE CROW\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-21 18:52:14'),
(29, NULL, 'admin', 'New video submitted', 'zia submitted \"Kindergarten | Learn Basic\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-21 18:53:37'),
(30, NULL, 'admin', 'New video submitted', 'zia submitted \"Story for Children\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-21 18:54:32'),
(31, 18, NULL, 'Video approved', 'Your video \"Story for Children\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-21 18:54:45'),
(32, 19, NULL, 'New video available!', 'A new video was just added to \"Little Explorers\": Story for Children', 'learning.php', 'fa-solid fa-clapperboard', 1, '2026-07-21 18:54:45'),
(33, 18, NULL, 'Video approved', 'Your video \"Kindergarten | Learn Basic\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-21 18:54:48'),
(34, 19, NULL, 'New video available!', 'A new video was just added to \"Little Explorers\": Kindergarten | Learn Basic', 'learning.php', 'fa-solid fa-clapperboard', 1, '2026-07-21 18:54:49'),
(35, 18, NULL, 'Video approved', 'Your video \"THE FOX AND THE CROW\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-21 18:54:51'),
(36, 19, NULL, 'New video available!', 'A new video was just added to \"Little Explorers\": THE FOX AND THE CROW', 'learning.php', 'fa-solid fa-clapperboard', 1, '2026-07-21 18:54:51'),
(37, NULL, 'admin', 'New quiz submitted', 'zia submitted \"Little Explorers Fun Quiz 2\" for review.', 'admin/admin_quizzes.php', 'fa-solid fa-circle-question', 0, '2026-07-21 19:11:55'),
(38, 18, NULL, 'Quiz rejected', 'Your quiz \"Little Explorers Fun Quiz 2\" was rejected. You can edit and resubmit it.', 'teacher/teacher_quizzes.php', 'fa-solid fa-circle-xmark', 0, '2026-07-21 19:17:16'),
(39, NULL, 'admin', 'New quiz submitted', 'zia submitted \"Little Explorers Fun Quiz 2\" for review.', 'admin/admin_quizzes.php', 'fa-solid fa-circle-question', 0, '2026-07-21 19:21:50'),
(40, 18, NULL, 'Quiz approved', 'Your quiz \"Little Explorers Fun Quiz 2\" was approved and is now live.', 'teacher/teacher_quizzes.php', 'fa-solid fa-circle-check', 0, '2026-07-21 19:22:08'),
(41, 19, NULL, 'New quiz available!', 'A new quiz is ready for you: Little Explorers Fun Quiz 2', 'quiz/quiz.php', 'fa-solid fa-circle-question', 0, '2026-07-21 19:22:08'),
(42, NULL, 'admin', 'New child account created', 'nimra created a child account for amna.', 'admin/admin_users.php', 'fa-solid fa-child', 0, '2026-07-21 21:10:14'),
(43, NULL, 'admin', 'New teacher application', 'nimra applied for \"Little Explorers\".', 'admin/admin_career_applications.php', 'fa-solid fa-chalkboard-user', 0, '2026-07-22 18:54:00'),
(44, NULL, 'admin', 'New user registered', 'nnn signed up as a parent.', 'admin/admin_users.php', 'fa-solid fa-user-plus', 0, '2026-07-22 19:50:27'),
(45, 18, NULL, 'Program assigned to you', 'You\'ve been assigned to manage a learning program. You can now add videos and quizzes to it.', 'teacher/teacher_program_videos.php', 'fa-solid fa-graduation-cap', 0, '2026-07-22 22:02:04'),
(46, NULL, 'admin', 'New quiz submitted', 'zia submitted \"Future Scientists Challenge Quiz 2\" for review.', 'admin/admin_quizzes.php', 'fa-solid fa-circle-question', 0, '2026-07-22 22:12:37'),
(47, 18, NULL, 'Quiz approved', 'Your quiz \"Future Scientists Challenge Quiz 2\" was approved and is now live.', 'teacher/teacher_quizzes.php', 'fa-solid fa-circle-check', 0, '2026-07-22 22:13:46'),
(48, 19, NULL, 'New quiz available!', 'A new quiz is ready for you: Future Scientists Challenge Quiz 2', 'quiz/quiz.php', 'fa-solid fa-circle-question', 0, '2026-07-22 22:13:46'),
(49, NULL, 'admin', 'New video submitted', 'zia submitted \"Journey Through Space\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:17:15'),
(50, NULL, 'admin', 'New video submitted', 'zia submitted \"Math Challenge Adventure\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:17:53'),
(51, NULL, 'admin', 'New video submitted', 'zia submitted \"Build Your First Game with Scratch\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:18:49'),
(52, NULL, 'admin', 'New video submitted', 'zia submitted \"Solve Fun Brain Puzzles\" for review.', 'admin/admin_program_videos.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:19:31'),
(53, 18, NULL, 'Video approved', 'Your video \"Solve Fun Brain Puzzles\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-22 22:19:55'),
(54, 19, NULL, 'New video available!', 'A new video was just added to \"Future Scientists\": Solve Fun Brain Puzzles', 'learning.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:19:55'),
(55, 18, NULL, 'Video approved', 'Your video \"Build Your First Game with Scratch\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-22 22:19:58'),
(56, 19, NULL, 'New video available!', 'A new video was just added to \"Future Scientists\": Build Your First Game with Scratch', 'learning.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:19:58'),
(57, 18, NULL, 'Video approved', 'Your video \"Math Challenge Adventure\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-22 22:19:59'),
(58, 19, NULL, 'New video available!', 'A new video was just added to \"Future Scientists\": Math Challenge Adventure', 'learning.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:19:59'),
(59, 18, NULL, 'Video approved', 'Your video \"Journey Through Space\" was approved and is now live.', 'teacher/teacher_program_videos.php', 'fa-solid fa-circle-check', 0, '2026-07-22 22:20:01'),
(60, 19, NULL, 'New video available!', 'A new video was just added to \"Future Scientists\": Journey Through Space', 'learning.php', 'fa-solid fa-clapperboard', 0, '2026-07-22 22:20:01'),
(61, NULL, 'admin', 'New user registered', 'zoha rafi signed up as a parent.', 'admin/admin_users.php', 'fa-solid fa-user-plus', 0, '2026-07-22 23:01:06'),
(62, NULL, 'admin', 'New user registered', 'zoha rafi signed up as a parent.', 'admin/admin_users.php', 'fa-solid fa-user-plus', 0, '2026-07-22 23:03:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_status` varchar(50) NOT NULL DEFAULT 'confirmed',
  `billing_name` varchar(150) DEFAULT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `order_status`, `billing_name`, `billing_address`, `contact_number`, `email`, `payment_method`, `payment_reference`, `delivery_charge`, `order_date`) VALUES
(1, 18, 3800.00, 'confirmed', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2026-06-28 23:35:20'),
(2, 18, 2050.00, 'confirmed', 'nimra zia', 'karachi flat no 3, karachi', '03012625363', NULL, 'cod', NULL, 250.00, '2026-07-09 21:30:05'),
(3, 19, 850.00, 'delivered', 'arwa rehan', '1st floor, lahore', '03130999999', NULL, 'jazzcash', '1111222', 250.00, '2026-07-14 20:55:13'),
(4, 19, 2350.00, 'confirmed', 'nimra zia', '1st floor, lahore', '03130999999', NULL, 'cod', NULL, 250.00, '2026-07-15 21:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `title`, `price`, `qty`, `image_path`) VALUES
(1, 1, 6, 'AR-Enabled Solar Explorers Atlas', 1800.00, 1, 'images/storeproduct/solar-atlas.png'),
(2, 1, 7, 'Phonics Pathway Audio Flashcards', 2000.00, 1, 'images/storeproduct/flashcards.png'),
(3, 2, 6, 'AR-Enabled Solar Explorers Atlas', 1800.00, 1, 'images/storeproduct/solar-atlas.png'),
(4, 3, 11, 'Wooden Rocket Puzzle blocks', 600.00, 1, 'images/storeproduct/product_1782690901_10ac8685.jpeg'),
(5, 4, 10, 'Mini Astronaut DIY Telescope', 2100.00, 1, 'images/storeproduct/product_1782690694_8c042453.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `parent_monitoring`
--

CREATE TABLE `parent_monitoring` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `last_watched_video` varchar(255) DEFAULT NULL,
  `last_action` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_monitoring`
--

INSERT INTO `parent_monitoring` (`id`, `parent_id`, `child_id`, `last_watched_video`, `last_action`, `updated_at`) VALUES
(1, 15, 19, 'Science Videos For Kids With Blippi', 'Watched video: Science Videos For Kids With Blippi (Video Library)', '2026-07-21 22:35:13'),
(2, 15, 28, NULL, NULL, '2026-07-21 08:55:53'),
(3, 15, 29, NULL, NULL, '2026-07-21 21:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT 'manual',
  `reference_note` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `confirmed_by` int(11) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `enrollment_id`, `amount`, `method`, `reference_note`, `status`, `confirmed_by`, `period_start`, `period_end`, `created_at`, `confirmed_at`) VALUES
(1, 1, 999.00, 'jazzcash', 'bdnzn', 'confirmed', 17, '2026-07-09', '2026-08-09', '2026-07-08 21:39:48', '2026-07-08 21:40:12'),
(2, 2, 1499.00, 'jazzcash', '1111', 'confirmed', 17, '2026-07-14', '2026-08-14', '2026-07-13 22:13:25', '2026-07-13 22:13:39'),
(3, 3, 1999.00, 'jazzcash', '12345', 'rejected', 17, '2026-07-21', '2026-08-21', '2026-07-20 22:38:14', '2026-07-20 23:15:10'),
(4, 3, 1999.00, 'easypaisa', 'bdnzn', 'pending', NULL, '2026-07-21', '2026-08-21', '2026-07-21 09:45:57', NULL),
(5, 3, 1999.00, 'easypaisa', 'bdnzn', 'confirmed', 17, '2026-07-21', '2026-08-21', '2026-07-21 09:45:57', '2026-07-22 21:34:23'),
(6, 5, 999.00, 'jazzcash', '111', 'pending', NULL, '2026-07-21', '2026-08-21', '2026-07-21 11:02:21', NULL),
(7, 6, 1499.00, 'jazzcash', '1111', 'pending', NULL, '2026-07-21', '2026-08-21', '2026-07-21 15:41:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `age_range` varchar(20) NOT NULL,
  `subjects` varchar(500) NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-graduation-cap',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `title`, `slug`, `age_range`, `subjects`, `monthly_price`, `icon`, `status`, `created_at`) VALUES
(1, 'Little Explorers', 'little-explorers', '3-6', 'ABC,Numbers,Colors,Shapes,Drawing,Stories,Rhymes,Speaking', 999.00, 'fa-child-reaching', 'active', '2026-07-03 21:34:23'),
(2, 'Future Scientists', 'future-scientists', '7-10', 'Science,Math,Space,Coding Basics,Experiments', 1499.00, 'fa-flask', 'active', '2026-07-03 21:34:23'),
(3, 'Young Coders', 'young-coders', '10-14', 'HTML,CSS,JavaScript,Python,AI Basics,Scratch', 1999.00, 'fa-code', 'active', '2026-07-03 21:34:23'),
(4, 'AI & Tech Innovators Lab', 'ai-tech-innovators', '12-16', 'AI Basics,Machine Learning Basics,App Development,Cybersecurity Basics,Data Science for Teens,Capstone Project', 2499.00, 'fa-microchip', 'active', '2026-07-03 21:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `program_videos`
--

CREATE TABLE `program_videos` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `video_url` varchar(500) NOT NULL,
  `video_type` enum('youtube','file') NOT NULL DEFAULT 'youtube',
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_videos`
--

INSERT INTO `program_videos` (`id`, `program_id`, `teacher_id`, `title`, `video_url`, `video_type`, `thumbnail_url`, `duration`, `order_index`, `status`, `admin_note`, `approved_by`, `approved_at`, `created_at`) VALUES
(2, 1, 18, 'Learn to write numbers 1-10', 'uploads/program_videos/pv_18_1783283537_732bf640.mp4', 'file', NULL, NULL, 2, 'approved', NULL, 17, '2026-07-05 20:32:49', '2026-07-05 20:32:17'),
(3, 1, 18, 'ABC Phonics | Sounds and Actions from A to Z', 'uploads/program_videos/pv_18_1783774810_cdad4862.mp4', 'file', NULL, NULL, 1, 'approved', NULL, 17, '2026-07-11 13:02:32', '2026-07-11 13:00:10'),
(4, 1, 18, 'Colors Song for Preschool & Kindergarten | Learn Basic Colors', 'uploads/program_videos/pv_18_1783775162_930a2b92.mp4', 'file', NULL, NULL, 3, 'approved', NULL, 17, '2026-07-11 13:08:03', '2026-07-11 13:06:02'),
(5, 1, 18, 'How Many Fingers | Kids Song | Action Songs for Children', 'uploads/program_videos/pv_18_1783775255_558ab1ce.mp4', 'file', NULL, NULL, 4, 'approved', NULL, 17, '2026-07-11 13:08:01', '2026-07-11 13:07:35'),
(6, 2, 18, 'EASY SCIENCE EXPERIMENTS', 'uploads/program_videos/pv_18_1783902947_387bd24d.mp4', 'file', NULL, NULL, 1, 'approved', NULL, 17, '2026-07-13 00:36:22', '2026-07-13 00:35:47'),
(7, 2, 18, 'How Your Brain Works?', 'uploads/program_videos/pv_18_1783903136_2ffbea51.mp4', 'file', NULL, NULL, 2, 'approved', NULL, 17, '2026-07-18 08:43:21', '2026-07-13 00:38:56'),
(8, 1, 18, 'THE FOX AND THE CROW', 'uploads/program_videos/pv_18_1784659934_4aa6076f.mp4', 'file', NULL, NULL, 5, 'approved', NULL, 17, '2026-07-21 18:54:51', '2026-07-21 18:52:14'),
(9, 1, 18, 'Kindergarten | Learn Basic', 'uploads/program_videos/pv_18_1784660017_b9bf5226.mp4', 'file', NULL, NULL, 6, 'approved', NULL, 17, '2026-07-21 18:54:48', '2026-07-21 18:53:37'),
(10, 1, 18, 'Story for Children', 'uploads/program_videos/pv_18_1784660072_297937a2.mp4', 'file', NULL, NULL, 7, 'approved', NULL, 17, '2026-07-21 18:54:45', '2026-07-21 18:54:32'),
(11, 2, 18, 'Journey Through Space', 'uploads/program_videos/pv_18_1784758635_e99f5d5e.mp4', 'file', NULL, NULL, 3, 'approved', NULL, 17, '2026-07-22 22:20:01', '2026-07-22 22:17:15'),
(12, 2, 18, 'Math Challenge Adventure', 'uploads/program_videos/pv_18_1784758673_6eaeca52.mp4', 'file', NULL, NULL, 4, 'approved', NULL, 17, '2026-07-22 22:19:59', '2026-07-22 22:17:53'),
(13, 2, 18, 'Build Your First Game with Scratch', 'uploads/program_videos/pv_18_1784758729_4c68c367.mp4', 'file', NULL, NULL, 5, 'approved', NULL, 17, '2026-07-22 22:19:58', '2026-07-22 22:18:49'),
(14, 2, 18, 'Solve Fun Brain Puzzles', 'uploads/program_videos/pv_18_1784758771_bfa53ffe.mp4', 'file', NULL, NULL, 6, 'approved', NULL, 17, '2026-07-22 22:19:55', '2026-07-22 22:19:31');

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `completed_lessons` int(11) DEFAULT 0,
  `progress_percent` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progress`
--

INSERT INTO `progress` (`id`, `user_id`, `course_id`, `completed_lessons`, `progress_percent`, `updated_at`) VALUES
(1, 19, 3, 0, 0, '2026-06-27 21:19:33'),
(2, 19, 2, 0, 0, '2026-06-27 21:19:41'),
(3, 19, 1, 0, 0, '2026-06-27 21:19:45');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_option` int(11) NOT NULL,
  `hint` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `question`, `option1`, `option2`, `option3`, `option4`, `correct_option`, `hint`, `category`) VALUES
(1, 'What is the capital of France?', 'Berlin', 'Madrid', 'Paris', 'Rome', 3, 'Known as the City of Light.', 'generalknowledge'),
(2, 'Which planet is the largest in our solar system?', 'Earth', 'Mars', 'Jupiter', 'Saturn', 3, 'It is a massive gas giant.', 'generalknowledge'),
(3, 'What is the chemical symbol for water?', 'O2', 'H2O', 'CO2', 'NaCl', 2, 'It contains two hydrogen atoms.', 'generalknowledge'),
(4, 'Who painted the Mona Lisa?', 'Van Gogh', 'Picasso', 'Da Vinci', 'Dalí', 3, 'A famous Italian polymath.', 'generalknowledge'),
(5, 'What is the hardest natural substance on Earth?', 'Gold', 'Iron', 'Diamond', 'Quartz', 3, 'It is often used in jewelry.', 'generalknowledge'),
(6, 'Which ocean is the largest?', 'Atlantic', 'Indian', 'Arctic', 'Pacific', 4, 'It covers more than 30% of the Earth.', 'generalknowledge'),
(7, 'What is the main language spoken in Brazil?', 'Spanish', 'Portuguese', 'English', 'French', 2, 'Similar to Spanish but distinct.', 'generalknowledge'),
(8, 'Who wrote \"Romeo and Juliet\"?', 'Charles Dickens', 'William Shakespeare', 'Mark Twain', 'Jane Austen', 2, 'The famous English playwright.', 'generalknowledge'),
(9, 'What is the square root of 64?', '6', '7', '8', '9', 3, 'Multiplied by itself, it equals 64.', 'generalknowledge'),
(10, 'Which element has the atomic number 1?', 'Helium', 'Oxygen', 'Hydrogen', 'Carbon', 3, 'It is the lightest element.', 'generalknowledge'),
(11, 'What is the capital of Japan?', 'Seoul', 'Beijing', 'Tokyo', 'Bangkok', 3, 'The city is known for its neon lights.', 'generalknowledge'),
(12, 'Which animal is known as the \"Ship of the Desert\"?', 'Horse', 'Camel', 'Elephant', 'Donkey', 2, 'It lives in hot, sandy environments.', 'generalknowledge'),
(13, 'What is the largest mammal in the world?', 'Elephant', 'Blue Whale', 'Giraffe', 'Shark', 2, 'It lives in the ocean.', 'generalknowledge'),
(14, 'In which year did World War II end?', '1940', '1945', '1950', '1939', 2, 'It followed a long global conflict.', 'generalknowledge'),
(15, 'What is the fastest land animal?', 'Lion', 'Cheetah', 'Horse', 'Antelope', 2, 'It is famous for its speed.', 'generalknowledge'),
(16, 'Which country is famous for the Eiffel Tower?', 'Italy', 'Germany', 'France', 'Spain', 3, 'It is located in Paris.', 'generalknowledge'),
(17, 'What is the primary gas in Earth\'s atmosphere?', 'Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen', 2, 'It makes up about 78%.', 'generalknowledge'),
(18, 'How many continents are there?', '5', '6', '7', '8', 3, 'Includes Africa, Asia, etc.', 'generalknowledge'),
(19, 'What is the currency of the United Kingdom?', 'Dollar', 'Euro', 'Pound', 'Yen', 3, 'Often called Sterling.', 'generalknowledge'),
(20, 'Which planet is closest to the Sun?', 'Venus', 'Mars', 'Mercury', 'Earth', 3, 'It is the smallest planet.', 'generalknowledge'),
(21, 'What comes next: 2, 4, 8, 16, ?', '20', '24', '32', '64', 3, 'Multiply by 2', 'iq'),
(22, 'How many sides does a hexagon have?', '5', '6', '7', '8', 2, 'Think of the prefix hex-', 'iq'),
(23, 'Which number is odd?', '12', '18', '21', '24', 3, 'Not divisible by 2', 'iq'),
(24, 'A dozen equals?', '10', '11', '12', '13', 3, 'Common counting term', 'iq'),
(25, 'What comes next: A, C, E, G, ?', 'H', 'I', 'J', 'K', 2, 'Skip one letter each time', 'iq'),
(26, 'What comes next: 2, 4, 8, 16, ?', '20', '24', '32', '64', 3, 'Multiply by 2', 'iq'),
(27, 'Which number is odd?', '12', '18', '21', '24', 3, 'Not divisible by 2', 'iq'),
(28, 'Find the missing number: 1, 4, 9, 16, ?', '20', '25', '30', '36', 2, 'Square numbers', 'iq'),
(29, 'How many sides does a hexagon have?', '5', '6', '7', '8', 2, 'Think of the prefix \"hex\"', 'iq'),
(30, 'What comes next: A, C, E, G, ?', 'H', 'I', 'J', 'K', 2, 'Skip one letter each time', 'iq'),
(31, 'Which is heavier?', '1kg Cotton', '1kg Iron', 'Both Equal', 'Cannot Tell', 3, 'Both weigh 1kg', 'iq'),
(32, 'What comes next: 5, 10, 15, 20, ?', '22', '25', '30', '35', 2, 'Add 5 each time', 'iq'),
(33, 'Which number does not belong?', '2', '4', '8', '9', 4, 'The others are even', 'iq'),
(34, 'How many months have 28 days?', '1', '2', '6', '12', 4, 'Every month has at least 28 days', 'iq'),
(35, 'Mirror image of 69 is?', '69', '96', '66', '99', 2, 'Reverse the digits', 'iq'),
(36, 'Which is a prime number?', '4', '6', '9', '11', 4, 'Only divisible by 1 and itself', 'iq'),
(37, 'What comes next: 1, 1, 2, 3, 5, ?', '6', '7', '8', '9', 3, 'Fibonacci sequence', 'iq'),
(38, 'A dozen equals?', '10', '11', '12', '13', 3, 'Common counting term', 'iq'),
(39, 'Which is different?', 'Dog', 'Cat', 'Tiger', 'Car', 4, 'Not an animal', 'iq'),
(40, 'If all roses are flowers and some flowers fade quickly, are all roses guaranteed to fade quickly?', 'Yes', 'No', 'Sometimes', 'Unknown', 4, 'Think carefully about the logic', 'iq'),
(41, 'What is H2O commonly known as?', 'Oxygen', 'Water', 'Hydrogen', 'Salt', 2, 'Essential for life', 'science'),
(42, 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Mercury', 2, 'Named after the Roman god of war', 'science'),
(43, 'What gas do humans need to breathe?', 'Nitrogen', 'Oxygen', 'Carbon Dioxide', 'Helium', 2, 'Needed for respiration', 'science'),
(44, 'Which organ pumps blood around the body?', 'Lungs', 'Brain', 'Heart', 'Kidney', 3, 'Part of the circulatory system', 'science'),
(45, 'What force keeps us on the ground?', 'Magnetism', 'Gravity', 'Friction', 'Electricity', 2, 'Discovered by Newton', 'science'),
(46, 'What is the center of our Solar System?', 'Moon', 'Earth', 'Sun', 'Mars', 3, 'It is a star', 'science'),
(47, 'Which planet is the largest?', 'Earth', 'Saturn', 'Jupiter', 'Neptune', 3, 'Gas giant', 'science'),
(48, 'How many bones are in an adult human body?', '201', '206', '210', '220', 2, 'Human skeleton', 'science'),
(49, 'What is the boiling point of water?', '50°C', '75°C', '100°C', '120°C', 3, 'At sea level', 'science'),
(50, 'What process do plants use to make food?', 'Respiration', 'Photosynthesis', 'Digestion', 'Evaporation', 2, 'Uses sunlight', 'science'),
(51, 'Which gas do plants absorb from the air?', 'Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen', 3, 'Used in photosynthesis', 'science'),
(52, 'What is the chemical symbol for gold?', 'Ag', 'Au', 'Go', 'Gd', 2, 'Periodic table element', 'science'),
(53, 'Which planet is closest to the Sun?', 'Venus', 'Earth', 'Mercury', 'Mars', 3, 'Smallest planet', 'science'),
(54, 'What is the freezing point of water?', '0°C', '10°C', '32°C', '100°C', 1, 'Ice forms at this temperature', 'science'),
(55, 'Which vitamin is produced when skin is exposed to sunlight?', 'A', 'B', 'C', 'D', 4, 'Sunshine vitamin', 'science'),
(56, 'Which instrument is used to see distant stars?', 'Microscope', 'Telescope', 'Periscope', 'Binoculars', 2, 'Used by astronomers', 'science'),
(57, 'What part of the plant absorbs water from soil?', 'Leaf', 'Flower', 'Root', 'Stem', 3, 'Underground part', 'science'),
(58, 'Which blood cells help fight infection?', 'Red Blood Cells', 'White Blood Cells', 'Platelets', 'Plasma', 2, 'Body defenders', 'science'),
(59, 'What is the hardest natural substance?', 'Iron', 'Gold', 'Diamond', 'Silver', 3, 'Used in cutting tools', 'science'),
(60, 'Which organ helps us think and control the body?', 'Heart', 'Liver', 'Brain', 'Lungs', 3, 'Located inside the skull', 'science'),
(61, 'What is the capital of France?', 'London', 'Berlin', 'Paris', 'Rome', 3, 'Known as the City of Lights', 'geography'),
(62, 'What is the capital of Pakistan?', 'Karachi', 'Lahore', 'Islamabad', 'Peshawar', 3, 'Federal capital city', 'geography'),
(63, 'Which is the largest continent?', 'Africa', 'Europe', 'Asia', 'Australia', 3, 'Contains China and India', 'geography'),
(64, 'Which ocean is the largest?', 'Atlantic', 'Indian', 'Pacific', 'Arctic', 3, 'Covers one-third of Earth', 'geography'),
(65, 'Mount Everest is located in?', 'India', 'China', 'Nepal', 'Japan', 3, 'Highest mountain in the world', 'geography'),
(66, 'What is the capital of China?', 'Shanghai', 'Beijing', 'Guangzhou', 'Hong Kong', 2, 'Political center of China', 'geography'),
(67, 'Which desert is the largest hot desert?', 'Gobi', 'Arabian', 'Sahara', 'Kalahari', 3, 'Located in Africa', 'geography'),
(68, 'What is the capital of Turkey?', 'Istanbul', 'Ankara', 'Izmir', 'Bursa', 2, 'Official capital city', 'geography'),
(69, 'Which country is called the Land of the Rising Sun?', 'China', 'Thailand', 'Japan', 'Korea', 3, 'Island nation in East Asia', 'geography'),
(70, 'Which river flows through Pakistan?', 'Nile', 'Amazon', 'Indus', 'Yangtze', 3, 'Pakistan’s major river', 'geography'),
(71, 'What is the capital of Australia?', 'Sydney', 'Melbourne', 'Canberra', 'Perth', 3, 'Not Sydney', 'geography'),
(72, 'Which continent is Egypt located in?', 'Asia', 'Africa', 'Europe', 'Australia', 2, 'North African country', 'geography'),
(73, 'What is the capital of Canada?', 'Toronto', 'Ottawa', 'Montreal', 'Vancouver', 2, 'National capital city', 'geography'),
(74, 'Which is the smallest continent?', 'Europe', 'Australia', 'Africa', 'Antarctica', 2, 'Also a country', 'geography'),
(75, 'K2 mountain is located in?', 'India', 'China', 'Pakistan', 'Nepal', 3, 'Second highest mountain', 'geography'),
(76, 'What is the capital of Italy?', 'Milan', 'Rome', 'Venice', 'Naples', 2, 'Ancient Roman city', 'geography'),
(77, 'Which ocean lies south of Pakistan?', 'Atlantic', 'Pacific', 'Indian', 'Arctic', 3, 'Borders Karachi coast', 'geography'),
(78, 'What is the capital of Saudi Arabia?', 'Jeddah', 'Riyadh', 'Medina', 'Dammam', 2, 'Largest city in Saudi Arabia', 'geography'),
(79, 'Which country has the largest area in the world?', 'USA', 'China', 'Russia', 'Canada', 3, 'Largest by land area', 'geography'),
(80, 'What is the capital of Germany?', 'Munich', 'Frankfurt', 'Berlin', 'Hamburg', 3, 'Historic European capital', 'geography'),
(81, 'Choose the correct spelling.', 'Recieve', 'Receive', 'Receeve', 'Receve', 2, 'I before E except after C', 'english'),
(82, 'What is the opposite of Hot?', 'Warm', 'Cold', 'Heat', 'Boil', 2, 'Think about winter', 'english'),
(83, 'What is the plural of Child?', 'Childs', 'Children', 'Childrens', 'Childes', 2, 'Irregular plural noun', 'english'),
(84, 'Which word is a noun?', 'Run', 'Quickly', 'Book', 'Blue', 3, 'Person, place, or thing', 'english'),
(85, 'What is the past tense of Go?', 'Goed', 'Gone', 'Went', 'Going', 3, 'Irregular verb', 'english'),
(86, 'Which word is a pronoun?', 'Table', 'He', 'Jump', 'Blue', 2, 'Replaces a noun', 'english'),
(87, 'Choose the correct article: ___ apple', 'A', 'An', 'The', 'No article', 2, 'Starts with a vowel sound', 'english'),
(88, 'Which word means the same as Happy?', 'Sad', 'Angry', 'Joyful', 'Tired', 3, 'Synonym question', 'english'),
(89, 'What is the opposite of Big?', 'Large', 'Huge', 'Small', 'Wide', 3, 'Antonym question', 'english'),
(90, 'Which word is a verb?', 'Chair', 'Run', 'Green', 'Tall', 2, 'Action word', 'english'),
(91, 'What is the plural of Mouse?', 'Mouses', 'Mice', 'Mousees', 'Mices', 2, 'Irregular plural', 'english'),
(92, 'Choose the correct spelling.', 'Beautifull', 'Beautiful', 'Beutiful', 'Beautifal', 2, 'Common adjective', 'english'),
(93, 'Which word is an adjective?', 'Run', 'Quickly', 'Blue', 'Jump', 3, 'Describes a noun', 'english'),
(94, 'What is the past tense of Eat?', 'Eated', 'Eating', 'Ate', 'Eats', 3, 'Irregular verb', 'english'),
(95, 'Which word means the same as Smart?', 'Clever', 'Lazy', 'Slow', 'Weak', 1, 'Synonym question', 'english'),
(96, 'What is the opposite of Up?', 'Over', 'Above', 'Down', 'High', 3, 'Direction word', 'english'),
(97, 'Which punctuation mark ends a question?', 'Comma', 'Period', 'Question Mark', 'Colon', 3, 'Used when asking something', 'english'),
(98, 'Choose the correct spelling.', 'Friend', 'Freind', 'Frend', 'Frind', 1, 'Common English word', 'english'),
(99, 'Which sentence is correct?', 'She go to school.', 'She goes to school.', 'She going school.', 'She gone school.', 2, 'Subject-verb agreement', 'english'),
(100, 'Which word is an adverb?', 'Quick', 'Quickly', 'Speed', 'Runner', 2, 'Describes a verb', 'english'),
(101, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Text Machine Language', 'Hyper Transfer Markup Language', 'Home Tool Markup Language', 1, NULL, 'coding'),
(102, 'Which language is used to style web pages?', 'HTML', 'Python', 'CSS', 'PHP', 3, NULL, 'coding'),
(103, 'Which language is mainly used to make web pages interactive?', 'CSS', 'HTML', 'JavaScript', 'SQL', 3, NULL, 'coding'),
(104, 'What symbol is used for comments in JavaScript?', '//', '#', '<!--', '**', 1, NULL, 'coding'),
(105, 'Which tag creates a hyperlink in HTML?', '<link>', '<a>', '<href>', '<url>', 2, NULL, 'coding'),
(106, 'What does CSS stand for?', 'Creative Style Sheets', 'Cascading Style Sheets', 'Computer Style Sheets', 'Colorful Style Sheets', 2, NULL, 'coding'),
(107, 'Which company developed JavaScript?', 'Google', 'Microsoft', 'Netscape', 'Apple', 3, NULL, 'coding'),
(108, 'Which HTML tag is used for the largest heading?', '<h6>', '<head>', '<heading>', '<h1>', 4, NULL, 'coding'),
(109, 'Which symbol is used to end a JavaScript statement?', ';', ':', '.', ',', 1, NULL, 'coding'),
(110, 'What is the correct file extension for JavaScript files?', '.java', '.js', '.script', '.jsx', 2, NULL, 'coding'),
(111, 'Which HTML tag is used to display an image?', '<img>', '<picture>', '<src>', '<image>', 1, NULL, 'coding'),
(112, 'What does PHP stand for?', 'Private Home Page', 'PHP Hypertext Processor', 'Programming Home Page', 'Personal Hypertext Processor', 2, NULL, 'coding'),
(113, 'Which symbol is used to select an ID in CSS?', '.', '#', '*', '&', 2, NULL, 'coding'),
(114, 'What is the output of 5 + 5 in programming?', '55', '10', '5', '0', 2, NULL, 'coding'),
(115, 'Which keyword is used to declare a variable in JavaScript?', 'var', 'make', 'new', 'create', 1, NULL, 'coding'),
(116, 'Which HTML tag is used to create a paragraph?', '<text>', '<paragraph>', '<p>', '<para>', 3, NULL, 'coding'),
(117, 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'System Query Language', 'Standard Question Language', 1, NULL, 'coding'),
(118, 'Which CSS property changes text color?', 'font-color', 'text-style', 'color', 'text-color', 3, NULL, 'coding'),
(119, 'Which loop repeats code while a condition is true?', 'if', 'switch', 'while', 'break', 3, NULL, 'coding'),
(120, 'Which symbol is used for multiplication in most programming languages?', 'x', '*', '%', '#', 2, NULL, 'coding');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `slot_number` tinyint(4) NOT NULL DEFAULT 1,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','archived') NOT NULL DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `title`, `category`, `program_id`, `slot_number`, `teacher_id`, `status`, `approved_by`, `approved_at`, `total_questions`, `created_at`) VALUES
(1, 'Basic Math Quiz', 'Math', NULL, 1, NULL, 'draft', NULL, NULL, 10, '2026-06-04 17:57:19'),
(2, 'HTML Beginner Quiz', 'Coding', NULL, 1, NULL, 'draft', NULL, NULL, 10, '2026-06-04 17:57:19'),
(3, 'English Grammar Quiz', 'English', NULL, 1, NULL, 'draft', NULL, NULL, 10, '2026-06-04 17:57:19'),
(6, 'Little Explorers Fun Quiz 1', 'Little Explorers', 1, 1, 18, 'approved', 17, '2026-07-11 13:41:57', 6, '2026-07-08 21:53:37'),
(7, 'Future Scientists Quiz 1', 'Future Scientists', 2, 1, 18, 'approved', 17, '2026-07-11 19:15:25', 6, '2026-07-11 19:07:30'),
(8, 'Little Explorers Fun Quiz 2', 'Little Explorers', 1, 2, 18, 'approved', 17, '2026-07-21 19:22:08', 10, '2026-07-21 19:01:00'),
(11, 'Future Scientists Quiz 2', 'Future Scientists', 2, 2, 18, 'approved', 17, '2026-07-22 22:13:46', 10, '2026-07-22 22:02:51');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `created_at`) VALUES
(1, 6, 'What comes after the letter A?', 'X', 'B', 'Z', 'Q', 'B', '2026-07-08 21:55:17'),
(2, 6, 'How many is this? 🍎🍎🍎', '2', '4', '3', '5', 'C', '2026-07-11 12:53:26'),
(3, 6, 'What color do you get when you mix blue and yellow?', 'Red', 'Green', 'purple', 'Orange', 'B', '2026-07-11 12:55:30'),
(4, 6, 'Which shape has 3 sides?', 'Circle', 'Square', 'riangle', 'Star', 'C', '2026-07-11 12:56:59'),
(5, 6, 'What color is a banana?', 'Red', 'Yellow', 'Blue', 'Black', 'B', '2026-07-11 12:57:48'),
(6, 6, 'Which one is a shape with 4 equal sides?', 'Circle', 'Square', 'Triangle', 'Oval', 'B', '2026-07-11 12:58:59'),
(7, 7, 'Which part of a plant makes food?', 'Root', 'Leaf', 'Flower', 'Stem', 'B', '2026-07-11 19:08:33'),
(8, 7, 'Which force keeps us on the ground?', 'Magnetism', 'Wind', 'Gravity', 'Electricity', 'C', '2026-07-11 19:09:44'),
(9, 7, 'Which of these is the biggest planet in our Solar System?', 'Earth', 'Mars', 'Jupiter', 'Mercury', 'C', '2026-07-11 19:10:32'),
(10, 7, 'Which coding concept means doing the same action again and again?', 'Variable', 'Loop', 'Function', 'Button', 'B', '2026-07-11 19:11:31'),
(11, 7, 'What happens when you mix baking soda and vinegar?', 'It freezes', 'It becomes solid', 'Nothing happens', 'It makes bubbles', 'D', '2026-07-11 19:12:38'),
(12, 7, 'The Sun is a...', 'Star', 'Moon', 'Planet', 'Comet', 'A', '2026-07-11 19:13:44'),
(13, 8, 'Which letter does Apple start with?', 'A', 'C', 'P', 'M', 'A', '2026-07-21 19:03:03'),
(14, 8, '20. Which planet do we live on?', 'Mars', 'Venus', 'Earth', 'Jupiter', 'C', '2026-07-21 19:05:44'),
(15, 8, 'Which is the biggest animal?', 'Dog', 'Cat', 'Rabbit', 'Elephant', 'D', '2026-07-21 19:07:12'),
(16, 8, 'Which body part helps you hear?', 'Feet', 'Hands', 'Ears', 'Nose', 'C', '2026-07-21 19:08:19'),
(17, 8, 'Which season is hot?', 'Summer', 'Winter', 'Autumn', 'Spring', 'A', '2026-07-21 19:09:26'),
(18, 8, 'Which vehicle runs on tracks?', 'bicycle', 'Boat', 'Train', 'Car', 'C', '2026-07-21 19:10:40'),
(19, 8, 'Which vehicle flies in the sky?', 'Car', 'bus', 'Train', 'Air plane', 'D', '2026-07-21 19:11:46'),
(20, 8, '. Which fruit is yellow?', 'Apple', 'Banana', 'Cherry', 'Grapes', 'B', '2026-07-21 19:18:40'),
(21, 8, 'Which animal says \"Woof\"?', 'Dog', 'Cat', 'Horse', 'Sheep', 'A', '2026-07-21 19:19:52'),
(22, 8, 'Which animal says \"Meow\"?', 'Dog', 'Cow', 'Cat', 'Duck', 'C', '2026-07-21 19:21:40'),
(23, 11, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Venus', 'Jupiter', 'B', '2026-07-22 22:12:28'),
(24, 11, 'What do plants need to make their own food?', 'Plastic', 'Sunlight', 'Sand', 'Rocks', 'B', '2026-07-22 22:12:28'),
(25, 11, 'What is 8 × 7?', '54', '56', '64', '48', 'B', '2026-07-22 22:12:28'),
(26, 11, 'Which gas do humans breathe in?', 'Carbon Dioxide', 'Oxygen', 'Hydrogen', 'Nitrogen', 'B', '2026-07-22 22:12:28'),
(27, 11, 'Which shape has 4 equal sides?', 'Circle', 'Triangle', 'Square', 'Oval', 'C', '2026-07-22 22:12:28'),
(28, 11, 'What is the largest planet in our Solar System?', 'Earth', 'Saturn', 'Jupiter', 'Mars', 'C', '2026-07-22 22:12:28'),
(29, 11, 'Which of these is a programming language?', 'HTML', 'Python', 'JPEG', 'WiFi', 'B', '2026-07-22 22:12:28'),
(30, 11, 'What is 100 ÷ 10?', '5', '10', '20', '50', 'B', '2026-07-22 22:12:28'),
(31, 11, 'Which part of a plant absorbs water?', 'Leaf', 'Flower', 'Root', 'Fruit', 'C', '2026-07-22 22:12:28'),
(32, 11, 'Which planet has beautiful rings?', 'Mercury', 'Mars', 'Saturn', 'Venus', 'C', '2026-07-22 22:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `user_id`, `quiz_id`, `score`, `total`, `percentage`, `passed`, `completed_at`) VALUES
(1, 19, 6, 5, 6, 83.33, 1, '2026-07-12 12:04:42'),
(2, 19, 8, 3, 10, 30.00, 0, '2026-07-28 21:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_retry_permissions`
--

CREATE TABLE `quiz_retry_permissions` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `granted_by` int(11) NOT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `consumed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_categories`
--

CREATE TABLE `store_categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon_class` varchar(50) DEFAULT 'fa-solid fa-graduation-cap'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_categories`
--

INSERT INTO `store_categories` (`id`, `slug`, `name`, `icon_class`) VALUES
(1, 'stem', 'STEM & Robotics Kits', 'fa-solid fa-flask-vial'),
(2, 'logic', 'Puzzles & Logic Building', 'fa-solid fa-brain'),
(3, 'books', 'Interactive Books & Kits', 'fa-solid fa-book-open'),
(4, 'early', 'Early Learning Kits', 'fa-solid fa-shapes');

-- --------------------------------------------------------

--
-- Table structure for table `store_products`
--

CREATE TABLE `store_products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) NOT NULL DEFAULT 'images/banner.png',
  `badge_tag` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_products`
--

INSERT INTO `store_products` (`id`, `category_id`, `title`, `description`, `price`, `stock`, `image_path`, `badge_tag`, `is_active`, `created_at`) VALUES
(1, 1, 'Astra-Rover Coding Robotics Kit', 'Build and configure your own mechanical rover. Links seamlessly with visual block programming modules to teach algorithmic commands.', 1500.00, 0, 'images/storeproduct/robot-kit.png', 'Top Pick', 1, '2026-06-11 18:45:37'),
(2, 1, 'Circuit Explorer Electronic Core', 'Safe, snap-together circuitry nodes that introduce real-world electrical logic, ambient lighting triggers, and physical kinetic switches.', 2000.00, 28, 'images/storeproduct/circuit-explorer.jpg', 'Best Seller', 1, '2026-06-11 18:45:37'),
(3, 2, 'CodeQuest Collaborative Strategy Board Game', 'An award-winning logic-building family tabletop game that guides players through loop structures, path optimization, and problem-solving patterns.', 1450.00, 0, 'images/storeproduct/board-game.jpg', 'Award Winner', 1, '2026-06-11 18:45:37'),
(4, 2, 'Cosmic Tetris 3D Spatial Grid', 'A tactile wooden spatial logic challenge featuring high-durability blocks designed to advance early pattern recognition and geometry concepts.', 1800.00, 0, 'images/storeproduct/spatial-grid.jpg', 'Trending', 1, '2026-06-11 18:45:37'),
(5, 3, 'Deep Space Logic Matrices Vol 1', 'An illustrated comic training log book mapping foundational math concepts, variable equations, and hidden structural logic quests.', 1525.00, 0, 'images/storeproduct/logic-book.png', 'New Release', 1, '2026-06-11 18:45:37'),
(6, 3, 'AR-Enabled Solar Explorers Atlas', 'An augmented-reality solar system encyclopedia that streams animated orbital mechanics on a device screen when pages are scanned.', 1800.00, 0, 'images/storeproduct/solar-atlas.png', 'AR Interactive', 1, '2026-06-11 18:45:37'),
(7, 4, 'Phonics Pathway Audio Flashcards', 'A sensory phonics learning tool with interactive tap-to-talk speech elements that teach pronunciation, vowel blends, and sight reading.', 2000.00, 0, 'images/storeproduct/flashcards.png', 'Kids Favorite', 1, '2026-06-11 18:45:37'),
(8, 4, 'Montessori Fraction Matching Trays', 'Tactile, vibrant wooden breakdown disks designed to make fractions, division scales, and proportional geometry clear and accessible.', 1950.00, 0, 'images/storeproduct/fraction-trays.png', 'Montessori Style', 1, '2026-06-11 18:45:37'),
(9, 4, '3D Solar System Model Kit', 'Bring the universe into your home with the 3D Solar System Model Kit. This exciting educational toy lets children build their own miniature solar system while learning about the planets, their order, and fascinating space facts', 2000.00, 0, 'images/storeproduct/product_1782673164_15a724bb.jpeg', 'New Release', 1, '2026-06-28 18:59:24'),
(10, 4, 'Mini Astronaut DIY Telescope', 'Inspire your child\'s curiosity with the Mini Astronaut DIY Telescope Toy. This fun and educational STEM toy lets kids build their own mini telescope while learning about space, astronomy, and basic science.', 2100.00, 30, 'images/storeproduct/product_1782690694_8c042453.jpeg', 'Best Seller', 1, '2026-06-28 23:51:34'),
(11, 2, 'Wooden Rocket Puzzle blocks', 'The Wooden Rocket Puzzle Blocks Toy is a fun and educational Montessori-inspired toy designed to develop children\'s creativity. Kids can stack, arrange, and assemble colorful wooden pieces to create a charming rocket while learning about shapes, balance, and spatial awareness.', 600.00, 16, 'images/storeproduct/product_1782690901_10ac8685.jpeg', 'Kids Favorite', 1, '2026-06-28 23:55:01');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_program_assignments`
--

CREATE TABLE `teacher_program_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_program_assignments`
--

INSERT INTO `teacher_program_assignments` (`id`, `teacher_id`, `program_id`, `assigned_by`, `assigned_at`) VALUES
(1, 18, 1, 17, '2026-07-21 18:50:39'),
(2, 18, 2, 17, '2026-07-22 22:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','student','parent','teacher') DEFAULT 'student',
  `account_status` enum('active','pending_deactivation','deactivated') NOT NULL DEFAULT 'active',
  `can_go_live` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `account_status`, `can_go_live`, `created_at`, `profile_pic`) VALUES
(15, 'nimra', 'nimrazia@gmail.com', '$2y$10$CqjwTg.Otf.UJS5AyX8GS.Sc4M0S4WzHeBf/XgbsMTupoX6Yl03qy', 'parent', 'active', 0, '2026-06-14 19:56:18', 'images/gg.png'),
(17, 'Admin User', 'admin@safekids.com', '$2y$10$qxEhCXTQb06buBFG6lFtxO.2BoCnXZFfH46Y6M6u0sFLACNcDtwSG', 'admin', 'active', 0, '2026-06-18 11:54:57', NULL),
(18, 'zia', 'zia@gmail.com', '$2y$10$h8/5pf50mxA/8R0/P1l2cuoIh7ZKCYZsMXFIO8DziLaZJ1F8tq9VW', 'teacher', 'active', 1, '2026-06-18 13:02:16', 'images/profile_pic/profile_18_1782684991.jpeg'),
(19, 'arwa', 'arwa@gmail.com', '$2y$10$.oMr7m5SK532soRvzO4zf.07/7DqvpVjgeqq/eKvrAIfhBvLAco8y', 'student', 'active', 0, '2026-06-18 13:09:51', 'images/profile_pic/profile_19_1783501449.jpg'),
(28, 'sam', 'sam@gmail.com', '$2y$10$5/v81ooxYSU7D9cIuO7WR.Z190Rb2bTIUa/QS7fXx4wofQlVwpR3G', 'student', 'active', 0, '2026-06-26 12:48:26', 'images/gg.png'),
(29, 'amna', 'amna@kids.safekidsspace.local', '$2y$10$YNge.ICbPwJpeEO67kegz.a7dj1dZgJF0LUouLlWts6iiug5UPhj.', 'student', 'active', 0, '2026-07-21 21:10:14', 'images/gg.png'),
(30, 'nnn', 'nimrazia46@gmail.com', '$2y$10$eJqyuU5/deT0jhUSEO43SeSy6XYCET69ys8zPxleJwAUIOQ5pmBvO', 'parent', 'active', 0, '2026-07-22 19:50:27', 'images/gg.png'),
(31, 'aa', 'aa@gmail.com', '$2y$10$hd4OTA7rEvMXUiXcwc9/ouq0obk/tMi9Lv27tmB.ISaHhFbaAyYUy', 'teacher', 'active', 0, '2026-07-22 20:24:07', 'images/gg.png'),
(33, 'zoha rafi', 'zoharafi23@gmail.com', '$2y$10$ldhQF0bGhg2azP5X9xYiL.EJ1TI.wfbNOAolB6QSFft29EF1jFbOa', 'parent', 'active', 0, '2026-07-22 23:03:42', 'images/gg.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `badge_id` int(11) DEFAULT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_game_progress`
--

CREATE TABLE `user_game_progress` (
  `user_id` int(11) NOT NULL,
  `game` varchar(30) NOT NULL,
  `state_json` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_game_progress`
--

INSERT INTO `user_game_progress` (`user_id`, `game`, `state_json`, `updated_at`) VALUES
(17, 'wordsearch', '{\"gridData\":[[\"S\",\"M\",\"I\",\"M\",\"K\",\"V\",\"D\",\"F\",\"X\",\"I\",\"I\",\"V\",\"S\",\"O\",\"K\"],[\"B\",\"T\",\"Q\",\"B\",\"E\",\"R\",\"M\",\"L\",\"W\",\"H\",\"K\",\"S\",\"A\",\"X\",\"K\"],[\"D\",\"M\",\"A\",\"D\",\"J\",\"C\",\"O\",\"F\",\"X\",\"T\",\"C\",\"O\",\"L\",\"Z\",\"B\"],[\"J\",\"Q\",\"T\",\"R\",\"R\",\"P\",\"O\",\"J\",\"I\",\"Z\",\"O\",\"N\",\"V\",\"K\",\"O\"],[\"S\",\"O\",\"N\",\"I\",\"E\",\"J\",\"N\",\"B\",\"X\",\"A\",\"M\",\"E\",\"D\",\"A\",\"A\"],[\"J\",\"X\",\"A\",\"E\",\"L\",\"K\",\"R\",\"S\",\"C\",\"T\",\"E\",\"X\",\"A\",\"G\",\"F\"],[\"P\",\"J\",\"G\",\"U\",\"T\",\"O\",\"A\",\"B\",\"H\",\"A\",\"T\",\"Q\",\"F\",\"Y\",\"B\"],[\"C\",\"G\",\"R\",\"S\",\"Z\",\"K\",\"P\",\"E\",\"X\",\"J\",\"U\",\"V\",\"Y\",\"D\",\"B\"],[\"Q\",\"G\",\"Y\",\"O\",\"U\",\"B\",\"N\",\"L\",\"L\",\"E\",\"T\",\"K\",\"H\",\"C\",\"Q\"],[\"N\",\"E\",\"A\",\"O\",\"C\",\"N\",\"M\",\"A\",\"R\",\"S\",\"D\",\"Z\",\"X\",\"Z\",\"I\"],[\"E\",\"D\",\"L\",\"L\",\"Z\",\"K\",\"R\",\"A\",\"X\",\"R\",\"T\",\"B\",\"S\",\"J\",\"U\"],[\"A\",\"Z\",\"T\",\"O\",\"A\",\"I\",\"E\",\"U\",\"E\",\"B\",\"M\",\"F\",\"X\",\"Z\",\"C\"],[\"R\",\"N\",\"Q\",\"K\",\"J\",\"X\",\"S\",\"T\",\"Q\",\"B\",\"G\",\"C\",\"G\",\"D\",\"C\"],[\"T\",\"N\",\"P\",\"B\",\"F\",\"M\",\"Y\",\"O\",\"Z\",\"L\",\"M\",\"I\",\"I\",\"R\",\"Z\"],[\"H\",\"P\",\"L\",\"A\",\"N\",\"E\",\"T\",\"B\",\"F\",\"W\",\"K\",\"Z\",\"V\",\"X\",\"X\"]],\"placedWords\":[{\"word\":\"GALAXY\",\"cells\":[{\"row\":8,\"col\":1},{\"row\":9,\"col\":2},{\"row\":10,\"col\":3},{\"row\":11,\"col\":4},{\"row\":12,\"col\":5},{\"row\":13,\"col\":6}],\"found\":false},{\"word\":\"ROCKET\",\"cells\":[{\"row\":7,\"col\":2},{\"row\":8,\"col\":3},{\"row\":9,\"col\":4},{\"row\":10,\"col\":5},{\"row\":11,\"col\":6},{\"row\":12,\"col\":7}],\"found\":false},{\"word\":\"PLANET\",\"cells\":[{\"row\":14,\"col\":1},{\"row\":14,\"col\":2},{\"row\":14,\"col\":3},{\"row\":14,\"col\":4},{\"row\":14,\"col\":5},{\"row\":14,\"col\":6}],\"found\":false},{\"word\":\"ORBIT\",\"cells\":[{\"row\":6,\"col\":5},{\"row\":5,\"col\":6},{\"row\":4,\"col\":7},{\"row\":3,\"col\":8},{\"row\":2,\"col\":9}],\"found\":false},{\"word\":\"COMET\",\"cells\":[{\"row\":2,\"col\":10},{\"row\":3,\"col\":10},{\"row\":4,\"col\":10},{\"row\":5,\"col\":10},{\"row\":6,\"col\":10}],\"found\":false},{\"word\":\"EARTH\",\"cells\":[{\"row\":10,\"col\":0},{\"row\":11,\"col\":0},{\"row\":12,\"col\":0},{\"row\":13,\"col\":0},{\"row\":14,\"col\":0}],\"found\":false},{\"word\":\"MOON\",\"cells\":[{\"row\":1,\"col\":6},{\"row\":2,\"col\":6},{\"row\":3,\"col\":6},{\"row\":4,\"col\":6}],\"found\":false},{\"word\":\"STAR\",\"cells\":[{\"row\":0,\"col\":0},{\"row\":1,\"col\":1},{\"row\":2,\"col\":2},{\"row\":3,\"col\":3}],\"found\":false},{\"word\":\"MARS\",\"cells\":[{\"row\":9,\"col\":6},{\"row\":9,\"col\":7},{\"row\":9,\"col\":8},{\"row\":9,\"col\":9}],\"found\":false},{\"word\":\"SUN\",\"cells\":[{\"row\":7,\"col\":3},{\"row\":8,\"col\":4},{\"row\":9,\"col\":5}],\"found\":false}],\"words\":[\"MOON\",\"ORBIT\",\"STAR\",\"COMET\",\"MARS\",\"GALAXY\",\"SUN\",\"ROCKET\",\"EARTH\",\"PLANET\"],\"score\":0,\"foundWordsCount\":0,\"timeLeft\":290}', '2026-07-28 21:12:56'),
(18, 'wordsearch', '{\"gridData\":[[\"V\",\"C\",\"X\",\"M\",\"E\",\"L\",\"H\",\"P\",\"W\",\"A\",\"Z\",\"N\",\"B\",\"U\",\"N\"],[\"O\",\"R\",\"Y\",\"F\",\"J\",\"K\",\"V\",\"L\",\"Q\",\"T\",\"F\",\"B\",\"W\",\"N\",\"D\"],[\"U\",\"R\",\"S\",\"T\",\"A\",\"R\",\"O\",\"A\",\"V\",\"R\",\"O\",\"C\",\"K\",\"E\",\"T\"],[\"E\",\"H\",\"J\",\"R\",\"R\",\"W\",\"B\",\"N\",\"K\",\"I\",\"R\",\"I\",\"I\",\"Y\",\"G\"],[\"F\",\"A\",\"P\",\"E\",\"A\",\"R\",\"Q\",\"E\",\"M\",\"I\",\"W\",\"L\",\"Z\",\"Z\",\"C\"],[\"G\",\"U\",\"R\",\"L\",\"L\",\"J\",\"S\",\"T\",\"S\",\"K\",\"A\",\"L\",\"S\",\"R\",\"A\"],[\"H\",\"D\",\"W\",\"T\",\"Z\",\"M\",\"V\",\"L\",\"B\",\"H\",\"B\",\"T\",\"T\",\"R\",\"O\"],[\"I\",\"T\",\"G\",\"B\",\"H\",\"X\",\"A\",\"N\",\"E\",\"J\",\"B\",\"I\",\"V\",\"A\",\"N\"],[\"P\",\"T\",\"Y\",\"A\",\"D\",\"P\",\"U\",\"R\",\"F\",\"D\",\"B\",\"M\",\"O\",\"O\",\"N\"],[\"Q\",\"R\",\"Q\",\"T\",\"L\",\"S\",\"B\",\"R\",\"S\",\"R\",\"S\",\"Y\",\"P\",\"D\",\"Q\"],[\"A\",\"Z\",\"C\",\"X\",\"E\",\"A\",\"W\",\"F\",\"O\",\"R\",\"N\",\"E\",\"S\",\"R\",\"I\"],[\"Y\",\"I\",\"V\",\"O\",\"U\",\"B\",\"X\",\"G\",\"O\",\"I\",\"S\",\"E\",\"S\",\"B\",\"F\"],[\"U\",\"C\",\"I\",\"T\",\"M\",\"Q\",\"L\",\"Y\",\"M\",\"C\",\"J\",\"O\",\"V\",\"T\",\"D\"],[\"F\",\"M\",\"U\",\"K\",\"X\",\"E\",\"E\",\"E\",\"Q\",\"D\",\"G\",\"V\",\"U\",\"C\",\"P\"],[\"K\",\"I\",\"S\",\"Y\",\"T\",\"X\",\"T\",\"I\",\"M\",\"M\",\"O\",\"W\",\"J\",\"B\",\"V\"]],\"placedWords\":[{\"word\":\"ROCKET\",\"cells\":[{\"row\":2,\"col\":9},{\"row\":2,\"col\":10},{\"row\":2,\"col\":11},{\"row\":2,\"col\":12},{\"row\":2,\"col\":13},{\"row\":2,\"col\":14}],\"found\":false},{\"word\":\"PLANET\",\"cells\":[{\"row\":0,\"col\":7},{\"row\":1,\"col\":7},{\"row\":2,\"col\":7},{\"row\":3,\"col\":7},{\"row\":4,\"col\":7},{\"row\":5,\"col\":7}],\"found\":false},{\"word\":\"GALAXY\",\"cells\":[{\"row\":7,\"col\":2},{\"row\":8,\"col\":3},{\"row\":9,\"col\":4},{\"row\":10,\"col\":5},{\"row\":11,\"col\":6},{\"row\":12,\"col\":7}],\"found\":false},{\"word\":\"COMET\",\"cells\":[{\"row\":10,\"col\":2},{\"row\":11,\"col\":3},{\"row\":12,\"col\":4},{\"row\":13,\"col\":5},{\"row\":14,\"col\":6}],\"found\":false},{\"word\":\"EARTH\",\"cells\":[{\"row\":3,\"col\":0},{\"row\":4,\"col\":1},{\"row\":5,\"col\":2},{\"row\":6,\"col\":3},{\"row\":7,\"col\":4}],\"found\":false},{\"word\":\"ORBIT\",\"cells\":[{\"row\":10,\"col\":8},{\"row\":9,\"col\":9},{\"row\":8,\"col\":10},{\"row\":7,\"col\":11},{\"row\":6,\"col\":12}],\"found\":false},{\"word\":\"MARS\",\"cells\":[{\"row\":6,\"col\":5},{\"row\":7,\"col\":6},{\"row\":8,\"col\":7},{\"row\":9,\"col\":8}],\"found\":false},{\"word\":\"STAR\",\"cells\":[{\"row\":2,\"col\":2},{\"row\":2,\"col\":3},{\"row\":2,\"col\":4},{\"row\":2,\"col\":5}],\"found\":false},{\"word\":\"MOON\",\"cells\":[{\"row\":8,\"col\":11},{\"row\":8,\"col\":12},{\"row\":8,\"col\":13},{\"row\":8,\"col\":14}],\"found\":false},{\"word\":\"SUN\",\"cells\":[{\"row\":9,\"col\":5},{\"row\":8,\"col\":6},{\"row\":7,\"col\":7}],\"found\":false}],\"words\":[\"SUN\",\"COMET\",\"ROCKET\",\"EARTH\",\"MARS\",\"STAR\",\"PLANET\",\"ORBIT\",\"GALAXY\",\"MOON\"],\"score\":0,\"foundWordsCount\":0,\"timeLeft\":266}', '2026-07-22 00:07:19'),
(19, 'mathmatch', '{\"moves\":7,\"level\":\"easy\",\"score\":650}', '2026-07-18 08:49:19'),
(19, 'wordsearch', '{\"gridData\":[[\"Z\",\"K\",\"D\",\"U\",\"I\",\"V\",\"Z\",\"F\",\"H\",\"Z\",\"E\",\"R\",\"F\",\"M\",\"M\"],[\"A\",\"Y\",\"O\",\"K\",\"E\",\"D\",\"N\",\"N\",\"E\",\"A\",\"R\",\"T\",\"H\",\"W\",\"E\"],[\"P\",\"B\",\"T\",\"H\",\"G\",\"D\",\"U\",\"A\",\"P\",\"J\",\"P\",\"M\",\"Y\",\"U\",\"W\"],[\"B\",\"I\",\"R\",\"G\",\"O\",\"S\",\"O\",\"R\",\"H\",\"M\",\"W\",\"J\",\"T\",\"S\",\"P\"],[\"H\",\"J\",\"L\",\"F\",\"K\",\"M\",\"H\",\"G\",\"C\",\"V\",\"R\",\"S\",\"R\",\"Z\",\"S\"],[\"H\",\"U\",\"K\",\"A\",\"G\",\"R\",\"V\",\"W\",\"Y\",\"V\",\"T\",\"A\",\"Z\",\"V\",\"G\"],[\"O\",\"Q\",\"K\",\"C\",\"A\",\"U\",\"B\",\"C\",\"C\",\"Q\",\"M\",\"L\",\"G\",\"H\",\"O\"],[\"H\",\"A\",\"M\",\"T\",\"L\",\"O\",\"I\",\"O\",\"S\",\"R\",\"A\",\"M\",\"O\",\"O\",\"N\"],[\"E\",\"D\",\"S\",\"I\",\"A\",\"C\",\"W\",\"L\",\"Q\",\"O\",\"O\",\"R\",\"B\",\"I\",\"T\"],[\"C\",\"A\",\"M\",\"K\",\"X\",\"O\",\"N\",\"J\",\"K\",\"C\",\"I\",\"T\",\"D\",\"H\",\"T\"],[\"V\",\"G\",\"F\",\"F\",\"Y\",\"M\",\"J\",\"G\",\"V\",\"K\",\"U\",\"R\",\"X\",\"E\",\"P\"],[\"Z\",\"R\",\"G\",\"G\",\"X\",\"E\",\"V\",\"Z\",\"H\",\"E\",\"P\",\"E\",\"N\",\"F\",\"P\"],[\"D\",\"V\",\"X\",\"E\",\"B\",\"T\",\"R\",\"K\",\"S\",\"T\",\"A\",\"A\",\"F\",\"A\",\"F\"],[\"V\",\"D\",\"K\",\"C\",\"W\",\"E\",\"L\",\"Z\",\"H\",\"Z\",\"L\",\"F\",\"Y\",\"G\",\"W\"],[\"Q\",\"P\",\"O\",\"P\",\"Z\",\"W\",\"Q\",\"A\",\"E\",\"P\",\"E\",\"C\",\"O\",\"T\",\"L\"]],\"placedWords\":[{\"word\":\"GALAXY\",\"cells\":[{\"row\":5,\"col\":4},{\"row\":6,\"col\":4},{\"row\":7,\"col\":4},{\"row\":8,\"col\":4},{\"row\":9,\"col\":4},{\"row\":10,\"col\":4}],\"found\":false},{\"word\":\"PLANET\",\"cells\":[{\"row\":14,\"col\":9},{\"row\":13,\"col\":10},{\"row\":12,\"col\":11},{\"row\":11,\"col\":12},{\"row\":10,\"col\":13},{\"row\":9,\"col\":14}],\"found\":false},{\"word\":\"ROCKET\",\"cells\":[{\"row\":7,\"col\":9},{\"row\":8,\"col\":9},{\"row\":9,\"col\":9},{\"row\":10,\"col\":9},{\"row\":11,\"col\":9},{\"row\":12,\"col\":9}],\"found\":false},{\"word\":\"ORBIT\",\"cells\":[{\"row\":8,\"col\":10},{\"row\":8,\"col\":11},{\"row\":8,\"col\":12},{\"row\":8,\"col\":13},{\"row\":8,\"col\":14}],\"found\":false},{\"word\":\"COMET\",\"cells\":[{\"row\":8,\"col\":5},{\"row\":9,\"col\":5},{\"row\":10,\"col\":5},{\"row\":11,\"col\":5},{\"row\":12,\"col\":5}],\"found\":false},{\"word\":\"EARTH\",\"cells\":[{\"row\":1,\"col\":8},{\"row\":1,\"col\":9},{\"row\":1,\"col\":10},{\"row\":1,\"col\":11},{\"row\":1,\"col\":12}],\"found\":false},{\"word\":\"MOON\",\"cells\":[{\"row\":7,\"col\":11},{\"row\":7,\"col\":12},{\"row\":7,\"col\":13},{\"row\":7,\"col\":14}],\"found\":false},{\"word\":\"MARS\",\"cells\":[{\"row\":6,\"col\":10},{\"row\":5,\"col\":11},{\"row\":4,\"col\":12},{\"row\":3,\"col\":13}],\"found\":false},{\"word\":\"STAR\",\"cells\":[{\"row\":8,\"col\":2},{\"row\":7,\"col\":3},{\"row\":6,\"col\":4},{\"row\":5,\"col\":5}],\"found\":false},{\"word\":\"SUN\",\"cells\":[{\"row\":3,\"col\":5},{\"row\":2,\"col\":6},{\"row\":1,\"col\":7}],\"found\":false}],\"words\":[\"MOON\",\"ORBIT\",\"GALAXY\",\"MARS\",\"COMET\",\"STAR\",\"PLANET\",\"EARTH\",\"ROCKET\",\"SUN\"],\"score\":0,\"foundWordsCount\":0,\"timeLeft\":146}', '2026-07-18 21:48:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_letter_progress`
--

CREATE TABLE `user_letter_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `letter` char(1) NOT NULL,
  `case_mode` enum('upper','lower') NOT NULL,
  `traced_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_letter_progress`
--

INSERT INTO `user_letter_progress` (`id`, `user_id`, `letter`, `case_mode`, `traced_at`) VALUES
(1, 19, 'A', 'upper', '2026-07-16 02:16:22'),
(4, 19, 'B', 'upper', '2026-07-16 01:25:20'),
(5, 19, 'C', 'upper', '2026-07-16 01:25:32'),
(6, 19, 'D', 'upper', '2026-07-16 01:25:40'),
(9, 19, 'E', 'upper', '2026-07-16 01:39:52'),
(11, 19, 'F', 'upper', '2026-07-16 02:16:30'),
(12, 18, 'A', 'upper', '2026-07-22 04:56:36'),
(13, 18, 'C', 'upper', '2026-07-22 04:57:03'),
(14, 18, 'B', 'upper', '2026-07-22 04:56:57'),
(16, 18, 'D', 'upper', '2026-07-22 04:57:17'),
(17, 18, 'E', 'upper', '2026-07-22 04:57:25'),
(18, 18, 'F', 'upper', '2026-07-22 04:57:52'),
(19, 18, 'G', 'upper', '2026-07-22 04:58:00'),
(20, 18, 'H', 'upper', '2026-07-22 04:58:06'),
(21, 18, 'I', 'upper', '2026-07-22 04:58:12'),
(22, 18, 'J', 'upper', '2026-07-22 04:58:16'),
(23, 18, 'K', 'upper', '2026-07-22 04:58:22'),
(24, 18, 'L', 'upper', '2026-07-22 04:58:27'),
(25, 18, 'M', 'upper', '2026-07-22 04:58:32'),
(26, 18, 'N', 'upper', '2026-07-22 04:58:37'),
(27, 18, 'O', 'upper', '2026-07-22 04:58:42'),
(28, 18, 'P', 'upper', '2026-07-22 04:58:47'),
(29, 18, 'Q', 'upper', '2026-07-22 04:58:52'),
(30, 18, 'R', 'upper', '2026-07-22 04:58:57'),
(31, 18, 'S', 'upper', '2026-07-22 04:59:01'),
(32, 18, 'T', 'upper', '2026-07-22 04:59:05'),
(33, 18, 'U', 'upper', '2026-07-22 04:59:09'),
(34, 18, 'V', 'upper', '2026-07-22 04:59:14'),
(35, 18, 'W', 'upper', '2026-07-22 04:59:20'),
(36, 18, 'X', 'upper', '2026-07-22 04:59:24'),
(37, 18, 'Y', 'upper', '2026-07-22 04:59:28'),
(38, 18, 'Z', 'upper', '2026-07-22 04:59:44');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `video_url` text DEFAULT NULL,
  `video_type` enum('youtube','file') NOT NULL DEFAULT 'youtube',
  `duration` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `course_id`, `title`, `video_url`, `video_type`, `duration`, `created_at`, `thumbnail_url`, `category`, `views`, `is_featured`) VALUES
(1, NULL, 'Science Experiments for Kids With Meekah', 'https://www.youtube.com/embed/n1jC9BGzKfk', 'youtube', '15:00', '2026-06-14 21:47:53', 'https://img.youtube.com/vi/n1jC9BGzKfk/hqdefault.jpg', 'Science', 0, 0),
(16, NULL, 'Explore Galaxy in 3D Animated Journey Through Space for Kids', 'https://www.youtube.com/embed/VQXKziB_5fs', 'youtube', '00/2:29', '2026-06-18 20:22:53', 'https://img.youtube.com/vi/VQXKziB_5fs/hqdefault.jpg', 'Space Studies', 0, 0),
(17, NULL, 'Coding for Kids', 'https://www.youtube.com/embed/j-3eArinB7E', 'youtube', '00/3:35', '2026-06-18 20:26:25', 'https://img.youtube.com/vi/j-3eArinB7E/hqdefault.jpg', 'Coding', 0, 0),
(18, NULL, 'Phonics Song with TWO Words', 'uploads/videos/vid_1784643692_9f7f3102.mp4', 'file', '00/4:05', '2026-06-18 20:29:02', 'uploads/video_thumbnails/thumb_1784643692_3f57629b.png', 'English', 0, 1),
(19, NULL, 'Addition and Subtraction with Dinosaurs', 'https://www.youtube.com/embed/igcoDFokKzU', 'youtube', '00/24:36', '2026-06-18 20:32:15', 'https://img.youtube.com/vi/igcoDFokKzU/hqdefault.jpg', 'Math', 1, 0),
(20, NULL, 'Learn Colors, ABCs & Fruits for Toddlers', 'https://www.youtube.com/embed/AxmFV4MEDpw', 'youtube', '00/37:41', '2026-06-18 21:14:20', 'https://img.youtube.com/vi/AxmFV4MEDpw/hqdefault.jpg', 'English', 0, 0),
(21, NULL, 'Coding for Kids Explained', 'https://www.youtube.com/embed/g1J4181W8ss', 'youtube', '00/3:30', '2026-06-18 21:15:52', 'https://img.youtube.com/vi/g1J4181W8ss/hqdefault.jpg', 'Coding', 2, 0),
(22, NULL, 'Introduction to Coding', 'uploads/videos/vid_1784646369_ac452400.mp4', 'file', '00/5:00', '2026-06-18 21:17:17', 'uploads/video_thumbnails/thumb_1784646369_6d20aa4c.png', 'Coding', 2, 0),
(23, NULL, 'Learn Parts of Body Names', 'uploads/videos/vid_1784642559_fc06827e.mp4', 'file', '00/4:05', '2026-06-18 21:19:26', 'uploads/video_thumbnails/thumb_1784642559_421961b1.jpeg', 'English', 2, 1),
(27, NULL, 'Science Videos For Kids With Blippi', 'uploads/videos/vid_1784642200_3205cc59.mp4', 'file', '00/3:35', '2026-07-21 13:56:40', 'uploads/video_thumbnails/thumb_1784642200_339f652d.png', 'Science', 31, 1);

-- --------------------------------------------------------

--
-- Table structure for table `video_watch_progress`
--

CREATE TABLE `video_watch_progress` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `program_video_id` int(11) NOT NULL,
  `watched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_watch_progress`
--

INSERT INTO `video_watch_progress` (`id`, `child_id`, `program_id`, `program_video_id`, `watched_at`) VALUES
(1, 19, 1, 3, '2026-07-11 13:02:47'),
(2, 19, 1, 2, '2026-07-11 13:02:51'),
(5, 19, 1, 4, '2026-07-11 13:08:40'),
(6, 19, 1, 5, '2026-07-11 13:08:42'),
(18, 19, 2, 7, '2026-07-18 08:43:43'),
(19, 19, 2, 6, '2026-07-18 08:44:04'),
(24, 19, 1, 8, '2026-07-21 19:13:26'),
(25, 19, 1, 9, '2026-07-21 19:13:27'),
(26, 19, 1, 10, '2026-07-21 19:13:28');

-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE `words` (
  `id` int(11) NOT NULL,
  `word` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `words`
--

INSERT INTO `words` (`id`, `word`, `category`, `status`) VALUES
(1, 'SUN', 'Space', 1),
(2, 'MOON', 'Space', 1),
(3, 'STAR', 'Space', 1),
(4, 'EARTH', 'Space', 1),
(5, 'MARS', 'Space', 1),
(6, 'COMET', 'Space', 1),
(7, 'ORBIT', 'Space', 1),
(8, 'ROCKET', 'Space', 1),
(9, 'PLANET', 'Space', 1),
(10, 'GALAXY', 'Space', 1),
(11, 'CAT', 'Animals', 1),
(12, 'DOG', 'Animals', 1),
(13, 'BIRD', 'Animals', 1),
(14, 'FISH', 'Animals', 1),
(15, 'LION', 'Animals', 1),
(16, 'TIGER', 'Animals', 1),
(17, 'ZEBRA', 'Animals', 1),
(18, 'SNAKE', 'Animals', 1),
(19, 'MONKEY', 'Animals', 1),
(20, 'RABBIT', 'Animals', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `career_applications`
--
ALTER TABLE `career_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `chatbot`
--
ALTER TABLE `chatbot`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deactivation_requests`
--
ALTER TABLE `deactivation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_child_program` (`child_id`,`program_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_enrollments_child` (`child_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `faq_categories`
--
ALTER TABLE `faq_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_scores`
--
ALTER TABLE `game_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_game` (`user_id`,`game_name`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `homework_assignments`
--
ALTER TABLE `homework_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kid_activity_logs`
--
ALTER TABLE `kid_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leaderboard_user` (`user_id`);

--
-- Indexes for table `live_classes`
--
ALTER TABLE `live_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `live_class_waitlist`
--
ALTER TABLE `live_class_waitlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_waitlist` (`user_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notif_user_role` (`user_id`,`target_role`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `parent_monitoring`
--
ALTER TABLE `parent_monitoring`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `idx_payments_enrollment_status` (`enrollment_id`,`status`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `program_videos`
--
ALTER TABLE `program_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_program_videos_program_status` (`program_id`,`status`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quizzes_program` (`program_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_retry_permissions`
--
ALTER TABLE `quiz_retry_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_child_quiz` (`child_id`,`quiz_id`),
  ADD KEY `idx_quiz_open` (`quiz_id`,`consumed_at`);

--
-- Indexes for table `store_categories`
--
ALTER TABLE `store_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `store_products`
--
ALTER TABLE `store_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `teacher_program_assignments`
--
ALTER TABLE `teacher_program_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_teacher_program` (`teacher_id`,`program_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- Indexes for table `user_game_progress`
--
ALTER TABLE `user_game_progress`
  ADD PRIMARY KEY (`user_id`,`game`);

--
-- Indexes for table `user_letter_progress`
--
ALTER TABLE `user_letter_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_letter_case` (`user_id`,`letter`,`case_mode`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `video_watch_progress`
--
ALTER TABLE `video_watch_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_child_video` (`child_id`,`program_video_id`),
  ADD KEY `idx_child_program` (`child_id`,`program_id`);

--
-- Indexes for table `words`
--
ALTER TABLE `words`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_words_category` (`category`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `career_applications`
--
ALTER TABLE `career_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot`
--
ALTER TABLE `chatbot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deactivation_requests`
--
ALTER TABLE `deactivation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `faq_categories`
--
ALTER TABLE `faq_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `game_scores`
--
ALTER TABLE `game_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homework_assignments`
--
ALTER TABLE `homework_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kid_activity_logs`
--
ALTER TABLE `kid_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `leaderboard`
--
ALTER TABLE `leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `live_classes`
--
ALTER TABLE `live_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_class_waitlist`
--
ALTER TABLE `live_class_waitlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `parent_monitoring`
--
ALTER TABLE `parent_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `program_videos`
--
ALTER TABLE `program_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_retry_permissions`
--
ALTER TABLE `quiz_retry_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_categories`
--
ALTER TABLE `store_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `store_products`
--
ALTER TABLE `store_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teacher_program_assignments`
--
ALTER TABLE `teacher_program_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_letter_progress`
--
ALTER TABLE `user_letter_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `video_watch_progress`
--
ALTER TABLE `video_watch_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `words`
--
ALTER TABLE `words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deactivation_requests`
--
ALTER TABLE `deactivation_requests`
  ADD CONSTRAINT `deactivation_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deactivation_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faqs`
--
ALTER TABLE `faqs`
  ADD CONSTRAINT `faqs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`);

--
-- Constraints for table `kid_activity_logs`
--
ALTER TABLE `kid_activity_logs`
  ADD CONSTRAINT `kid_activity_logs_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `store_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_monitoring`
--
ALTER TABLE `parent_monitoring`
  ADD CONSTRAINT `parent_monitoring_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_monitoring_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `program_videos`
--
ALTER TABLE `program_videos`
  ADD CONSTRAINT `program_videos_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_videos_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_videos_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_products`
--
ALTER TABLE `store_products`
  ADD CONSTRAINT `store_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_program_assignments`
--
ALTER TABLE `teacher_program_assignments`
  ADD CONSTRAINT `tpa_program_fk` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tpa_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
