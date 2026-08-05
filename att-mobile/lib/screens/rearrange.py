import re

with open('dashboard_screen.dart', 'r', encoding='utf-8') as f:
    content = f.read()

# I will find the exact blocks using string splits or regex.
header_end = content.find('                // Attend Card')
attend_card_end = content.find('                // Kunjungan Lapangan')
kunjungan_end = content.find('                // Target & Performa')
target_end = content.find('                // Team Stats')
team_end = content.find('                // Menu Lainnya')
menu_end = content.find('                // Log Aktivitas')

part1 = content[:header_end]
attend_card_block = content[header_end:attend_card_end]
kunjungan_block = content[attend_card_end:kunjungan_end]
target_block = content[kunjungan_end:target_end]
team_block = content[target_end:team_end]
menu_block = content[team_end:menu_end]
part2 = content[menu_end:]

# Fix target block condition
target_block = """                // Target & Performa (For All Users)
                const DashboardStatsWidget(),
                const SizedBox(height: 15),

"""

# Fix team block condition
team_block = """                // Team Stats (For users with subordinates/team)
                if (dashboardProvider.totalTeam > 0 || positionName.toUpperCase() == 'TL') ...[
                  const TeamStatsWidget(),
                  const SizedBox(height: 15),
                ],

"""

# New order: menu, target, team, attend, kunjungan
new_content = part1 + menu_block + target_block + team_block + attend_card_block + kunjungan_block + part2

with open('dashboard_screen.dart', 'w', encoding='utf-8') as f:
    f.write(new_content)
