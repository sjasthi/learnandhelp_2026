-- Test registrations for python_101_scores.csv progress report matching.
-- Student_Email matches the CSV exactly so the preview/send pages show "Matched".
-- Sponsor1_Email is set to the admin/test email so DEV-mode reports are easy to verify.
-- Run once in phpMyAdmin, then upload the CSV again.

INSERT INTO `registrations`
  (`offering_id`, `Sponsor1_Name`, `Sponsor1_Email`, `Student_Name`, `Student_Email`,
   `Class_Id`, `Created_Time`, `Batch_Name`, `Payment_method`, `payment_status`)
VALUES
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Abhinav sai Manikonda',   'xbhinxvsximxnikoncx@gmxil.com',      1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Aditi Boggarapu',          'xcitibog@gmxil.com',                  1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Advaith Amba',             'm2xcvxith@gmxil.com',                 1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Agastya Ivaturi',          'xgxstyxivxturi@gmxil.com',            1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Anay Bhagatwala',          'xnxybhxgxtwxlx@gmxil.com',           1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Aneesh Habbu',             'xneesh.hxbbu@gmxil.com',              1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Arjun Kadiyala',           'xkxciyxlx02@gmxil.com',               1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Dhruvan Teja Talapaneni',  'chruvxntejx.txlxpxneni@gmxil.com',   1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Haasini Kondisetti',       'hxxsini.koncisetti@gmxil.com',        1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Kandula Vasavi',           'vxsxvikxnculx9@gmxil.com',            1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Krishna Aluri',            'xlurikrish2012@gmxil.com',            1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Meghana Burra',            'meghxnxkburrx@gmxil.com',             1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Mohith',                   'mohito90094@gmxil.com',               1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Mohnish Yakkanti',         'mohnish.yxkkxnti@gmxil.com',          1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Nainika Sai Karumudi',     'nskxrumuci@gmxil.com',                1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Nandini Kondisetti',       'nxncini.koncisetti@gmxil.com',        1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Nathaniel Chacko',         'nxtechxcko@gmxil.com',                1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Nik FR',                   'sxinikhil092009@gmxil.com',           1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Pari G',                   'pxrinichi.gollxpuci@gmxil.com',      1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Prateek Rathikindi',       'prxteek.rxthikinci@gmxil.com',        1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Rishik Kollu',             'kollurishik@gmxil.com',               1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Shriyan Deshmukh',         'shriyxn.ceshmukh@gmxil.com',          1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Srihita Ganti',            'gxntisrihitx@gmxil.com',              1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Subodha Popuri',           'subochxpopuri@gmxil.com',             1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Swara Lokhande',           'veenxlokhxnce17@gmxil.com',           1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Varun Aditya Nanduri',     'nxncurivxrunxcityx@gmxil.com',        1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Vik Manda',                'vikrxntmxncx002@gmxil.com',           1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Yaswanth Grandhi',         'yxswxnthgrxnchi1@gmxil.com',          1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Pesky Bird',               'prish.prish.thxkkxr@gmxil.com',       1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Shilpa Sunkara',           'shilpx.sssr@gmxil.com',               1, '2026-02-26', '2025-2026', '', 'paid'),
  (6, 'Test Parent', 'mekics499project24@gmail.com', 'Vishwak Guntupalli',       'guntupxllivishwxk@gmxil.com',         1, '2026-02-26', '2025-2026', '', 'paid');
