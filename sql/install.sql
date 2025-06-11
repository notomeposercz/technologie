CREATE TABLE IF NOT EXISTS `PREFIX_technologie` (
  `id_technologie` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `image` varchar(255),
  `position` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_technologie`),
  KEY `active` (`active`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vložení ukázkových dat pro testování
INSERT INTO `PREFIX_technologie` (`name`, `description`, `image`, `position`, `active`, `date_add`, `date_upd`) VALUES
('Sítotisk', 'Tradiční technika potisku vhodná pro větší náklady. Vynikající kvalita a trvanlivost.', '', 1, 1, NOW(), NOW()),
('Digitální potisk', 'Moderní technika umožňující potisk malých nákladů s vysokým rozlišením.', '', 2, 1, NOW(), NOW()),
('Vyšívání', 'Elegantní technika pro textilní výrobky. Luxusní vzhled a dlouhá životnost.', '', 3, 1, NOW(), NOW()),
('Termotransfer', 'Rychlá technika pro potisk textilu pomocí speciálních fólií.', '', 4, 1, NOW(), NOW()),
('Gravírování', 'Precizní technika pro kovové a plastové předměty.', '', 5, 1, NOW(), NOW());
