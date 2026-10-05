import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches

# Clean backend and fonts
plt.switch_backend('Agg')
plt.rcParams['font.sans-serif'] = ['Segoe UI', 'Arial', 'Calibri', 'DejaVu Sans']
plt.rcParams['axes.unicode_minus'] = False

def create_architecture_diagram(output_path):
    fig, ax = plt.subplots(figsize=(12, 6.5), dpi=300)
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 6.5)
    ax.axis('off')

    fig.patch.set_facecolor('#FFFFFF')
    ax.set_facecolor('#FFFFFF')

    # Title Banner
    ax.text(6, 6.15, "UniThrift 3-Tier Web Application Architecture", 
            ha='center', va='center', fontsize=15, fontweight='bold', color='#0F172A')
    ax.text(6, 5.85, "Client-Side Presentation  -->  Application & Security Logic  -->  Relational Data Persistence", 
            ha='center', va='center', fontsize=9.5, fontstyle='italic', color='#475569')

    # Tier 1: Client Layer
    rect1 = patches.FancyBboxPatch((0.4, 0.8), 3.2, 4.7, boxstyle="round,pad=0.1,rounding_size=0.2", 
                                  facecolor='#F8FAFC', edgecolor='#2563EB', linewidth=2)
    ax.add_patch(rect1)
    hbar1 = patches.FancyBboxPatch((0.4, 4.9), 3.2, 0.6, boxstyle="round,pad=0.05,rounding_size=0.1", 
                                   facecolor='#2563EB', edgecolor='#2563EB', linewidth=1)
    ax.add_patch(hbar1)
    ax.text(2.0, 5.2, "TIER 1: PRESENTATION LAYER", ha='center', va='center', fontsize=9.5, fontweight='bold', color='#FFFFFF')
    ax.text(2.0, 4.95, "(Client Browser / Frontend)", ha='center', va='center', fontsize=8, color='#DBEAFE')

    t1_items = [
        ("HTML5 Semantic Layout", "Responsive templates, modals & grid views"),
        ("CSS3 Modern Stylesheet", "CSS Variables, Dark/Light Mode Switcher"),
        ("Vanilla JavaScript (ES6)", "Live search debounce & category filters"),
        ("Client State & Storage", "localStorage for user theme preference"),
        ("DOM Manipulation Engine", "Dynamic table updates & modal triggers"),
        ("Async Handlers & Fetch", "Client-side form validation before POST")
    ]
    y_pos = 4.4
    for title, desc in t1_items:
        sub_box = patches.FancyBboxPatch((0.6, y_pos - 0.45), 2.8, 0.52, boxstyle="round,pad=0.04,rounding_size=0.08",
                                         facecolor='#EFF6FF', edgecolor='#BFDBFE', linewidth=1)
        ax.add_patch(sub_box)
        ax.text(0.75, y_pos - 0.15, title, fontsize=8.5, fontweight='bold', color='#1E3A8A')
        ax.text(0.75, y_pos - 0.35, desc, fontsize=7, color='#3B82F6')
        y_pos -= 0.65

    # Tier 2: Application / Server Layer
    rect2 = patches.FancyBboxPatch((4.4, 0.8), 3.2, 4.7, boxstyle="round,pad=0.1,rounding_size=0.2", 
                                  facecolor='#F8FAFC', edgecolor='#0D9488', linewidth=2)
    ax.add_patch(rect2)
    hbar2 = patches.FancyBboxPatch((4.4, 4.9), 3.2, 0.6, boxstyle="round,pad=0.05,rounding_size=0.1", 
                                   facecolor='#0D9488', edgecolor='#0D9488', linewidth=1)
    ax.add_patch(hbar2)
    ax.text(6.0, 5.2, "TIER 2: APPLICATION LAYER", ha='center', va='center', fontsize=9.5, fontweight='bold', color='#FFFFFF')
    ax.text(6.0, 4.95, "(Apache 2.4 + PHP 8 Engine)", ha='center', va='center', fontsize=8, color='#CCFBF1')

    t2_items = [
        ("Authentication Controller", "Session management, role guard (Student/Admin)"),
        ("Password Cryptography", "BCRYPT hashing (password_hash & verify)"),
        ("CRUD & Business Logic", "Ownership check (item.user_id === session.uid)"),
        ("Defensive Security Middleware", "SQLi defense, XSS htmlspecialchars, CSRF"),
        ("PDO Data Access Abstraction", "Prepared statements & parameterized queries"),
        ("Setup Wizard (install.php)", "Automated DB creation & sample data seeding")
    ]
    y_pos = 4.4
    for title, desc in t2_items:
        sub_box = patches.FancyBboxPatch((4.6, y_pos - 0.45), 2.8, 0.52, boxstyle="round,pad=0.04,rounding_size=0.08",
                                         facecolor='#F0FDF4', edgecolor='#A7F3D0', linewidth=1)
        ax.add_patch(sub_box)
        ax.text(4.75, y_pos - 0.15, title, fontsize=8.5, fontweight='bold', color='#065F46')
        ax.text(4.75, y_pos - 0.35, desc, fontsize=7, color='#059669')
        y_pos -= 0.65

    # Tier 3: Database & Storage Layer
    rect3 = patches.FancyBboxPatch((8.4, 0.8), 3.2, 4.7, boxstyle="round,pad=0.1,rounding_size=0.2", 
                                  facecolor='#F8FAFC', edgecolor='#7C3AED', linewidth=2)
    ax.add_patch(rect3)
    hbar3 = patches.FancyBboxPatch((8.4, 4.9), 3.2, 0.6, boxstyle="round,pad=0.05,rounding_size=0.1", 
                                   facecolor='#7C3AED', edgecolor='#7C3AED', linewidth=1)
    ax.add_patch(hbar3)
    ax.text(10.0, 5.2, "TIER 3: DATA PERSISTENCE", ha='center', va='center', fontsize=9.5, fontweight='bold', color='#FFFFFF')
    ax.text(10.0, 4.95, "(MariaDB / MySQL Database)", ha='center', va='center', fontsize=8, color='#EDE9FE')

    t3_items = [
        ("Table: users", "Student profiles, credentials, role, contacts"),
        ("Table: items", "Inventory, prices, condition, meetup spot"),
        ("Table: contact_messages", "Inquiries, subjects, timestamps, read state"),
        ("Table: notifications", "System announcements, alerts & user notices"),
        ("Table: reports", "Content moderation, flags, review notes"),
        ("Asset File Storage (Local)", "Uploaded gear photos in /assets/uploads/")
    ]
    y_pos = 4.4
    for title, desc in t3_items:
        sub_box = patches.FancyBboxPatch((8.6, y_pos - 0.45), 2.8, 0.52, boxstyle="round,pad=0.04,rounding_size=0.08",
                                         facecolor='#FAF5FF', edgecolor='#DDD6FE', linewidth=1)
        ax.add_patch(sub_box)
        ax.text(8.75, y_pos - 0.15, title, fontsize=8.5, fontweight='bold', color='#5B21B6')
        ax.text(8.75, y_pos - 0.35, desc, fontsize=7, color='#7C3AED')
        y_pos -= 0.65

    # Connectors
    ax.annotate('', xy=(4.35, 3.4), xytext=(3.65, 3.4),
                arrowprops=dict(arrowstyle="->", color="#1E293B", lw=2, mutation_scale=15))
    ax.annotate('', xy=(3.65, 2.7), xytext=(4.35, 2.7),
                arrowprops=dict(arrowstyle="->", color="#1E293B", lw=2, mutation_scale=15))
    ax.text(4.0, 3.65, "HTTP POST/GET", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#1E293B')
    ax.text(4.0, 3.2, "HTML / JSON", ha='center', va='center', fontsize=7, color='#475569')
    ax.text(4.0, 2.45, "Session / Cookies", ha='center', va='center', fontsize=7, color='#475569')

    ax.annotate('', xy=(8.35, 3.4), xytext=(7.65, 3.4),
                arrowprops=dict(arrowstyle="->", color="#1E293B", lw=2, mutation_scale=15))
    ax.annotate('', xy=(7.65, 2.7), xytext=(8.35, 2.7),
                arrowprops=dict(arrowstyle="->", color="#1E293B", lw=2, mutation_scale=15))
    ax.text(8.0, 3.65, "SQL Prepared", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#1E293B')
    ax.text(8.0, 3.2, "Bound Params", ha='center', va='center', fontsize=7, color='#475569')
    ax.text(8.0, 2.45, "Result Sets (Row)", ha='center', va='center', fontsize=7, color='#475569')

    # External Integration
    ext_box = patches.FancyBboxPatch((0.8, 0.1), 10.4, 0.45, boxstyle="round,pad=0.05,rounding_size=0.1",
                                    facecolor='#ECFDF5', edgecolor='#10B981', linewidth=1)
    ax.add_patch(ext_box)
    ax.text(6.0, 0.32, ">> External Peer Communication Interface: WhatsApp Click-to-Chat API (wa.me) & Direct Telephone Protocol (tel:)",
            ha='center', va='center', fontsize=8, fontweight='bold', color='#047857')

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated Architecture Diagram: {output_path}")

def create_erd_diagram(output_path):
    fig, ax = plt.subplots(figsize=(12, 7.2), dpi=300)
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 7.2)
    ax.axis('off')

    fig.patch.set_facecolor('#FFFFFF')
    ax.set_facecolor('#FFFFFF')

    # Title
    ax.text(6, 6.9, "UniThrift Relational Entity-Relationship Diagram (ERD)", 
            ha='center', va='center', fontsize=15, fontweight='bold', color='#0F172A')
    ax.text(6, 6.6, "MariaDB / MySQL Normalized Schema (Crow's Foot Notation & Foreign Key Constraints)", 
            ha='center', va='center', fontsize=9.5, fontstyle='italic', color='#475569')

    def draw_entity(x, y, w, h, title, pk_list, fk_list, attr_list, header_color):
        box = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.05,rounding_size=0.12",
                                     facecolor='#FFFFFF', edgecolor='#334155', linewidth=1.5)
        ax.add_patch(box)
        header = patches.FancyBboxPatch((x, y + h - 0.55), w, 0.55, boxstyle="round,pad=0.03,rounding_size=0.08",
                                        facecolor=header_color, edgecolor=header_color, linewidth=1)
        ax.add_patch(header)
        ax.text(x + w/2, y + h - 0.28, title, ha='center', va='center', fontsize=10, fontweight='bold', color='#FFFFFF')

        curr_y = y + h - 0.8
        for pk in pk_list:
            ax.text(x + 0.15, curr_y, "[PK]", fontsize=7.5, fontweight='bold', color='#B91C1C')
            ax.text(x + 0.8, curr_y, pk, fontsize=7.5, fontweight='bold', color='#0F172A')
            curr_y -= 0.28
        for fk in fk_list:
            ax.text(x + 0.15, curr_y, "[FK]", fontsize=7.5, fontweight='bold', color='#2563EB')
            ax.text(x + 0.8, curr_y, fk, fontsize=7.5, fontstyle='italic', color='#0F172A')
            curr_y -= 0.28
        for attr in attr_list:
            ax.text(x + 0.25, curr_y, "-", fontsize=8, color='#64748B')
            ax.text(x + 0.45, curr_y, attr, fontsize=7.2, color='#334155')
            curr_y -= 0.28

    # Entity 1: users
    draw_entity(0.5, 2.5, 3.0, 3.6, "users", 
                ["id : INT (Auto-Inc)"],
                [],
                ["student_id : VARCHAR(30) [U]", "full_name : VARCHAR(100)", "email : VARCHAR(100) [U]", 
                 "phone : VARCHAR(25)", "department : VARCHAR(50)", "role : VARCHAR(20)", 
                 "password_hash : VARCHAR(255)", "created_at : TIMESTAMP"],
                '#1E3A8A')

    # Entity 2: items
    draw_entity(4.5, 1.8, 3.2, 4.4, "items",
                ["id : INT (Auto-Inc)"],
                ["user_id : INT (users.id)"],
                ["title : VARCHAR(150)", "category : ENUM (5 categories)", "course_code : VARCHAR(20)",
                 "item_condition : ENUM (3 types)", "original_price : DECIMAL(10,2)", "selling_price : DECIMAL(10,2)",
                 "description : TEXT", "meetup_location : VARCHAR(120)", "image_url : VARCHAR(255)", 
                 "status : ENUM ('Available'...)", "created_at / updated_at : TS"],
                '#0D9488')

    # Entity 3: reports
    draw_entity(8.7, 3.8, 2.9, 2.6, "reports",
                ["id : INT (Auto-Inc)"],
                ["item_id : INT (items.id)", "reporter_id : INT (users.id)"],
                ["reason : VARCHAR(100)", "details : TEXT", "status : ENUM ('Pending'...)", 
                 "admin_notes : TEXT", "created_at : TIMESTAMP"],
                '#B45309')

    # Entity 4: notifications
    draw_entity(8.7, 0.8, 2.9, 2.6, "notifications",
                ["id : INT (Auto-Inc)"],
                ["user_id : INT (users.id)", "item_id : INT (items.id)"],
                ["title : VARCHAR(150)", "message : TEXT", "target_audience : VARCHAR(30)", 
                 "alert_type : VARCHAR(20)", "is_read : TINYINT(1)", "created_at : TIMESTAMP"],
                '#6D28D9')

    # Entity 5: contact_messages
    draw_entity(0.5, 0.4, 3.0, 1.8, "contact_messages",
                ["id : INT (Auto-Inc)"],
                [],
                ["name : VARCHAR(100)", "email : VARCHAR(100)", "phone : VARCHAR(30)", 
                 "subject : VARCHAR(150)", "message : TEXT", "status : ENUM ('read'/'unread')"],
                '#475569')

    # Connectors
    ax.annotate('', xy=(4.5, 4.3), xytext=(3.5, 4.3),
                arrowprops=dict(arrowstyle="-|>", color="#1E3A8A", lw=2))
    ax.text(3.6, 4.45, "1", fontsize=9, fontweight='bold', color="#1E3A8A")
    ax.text(4.25, 4.45, "N", fontsize=9, fontweight='bold', color="#1E3A8A")
    ax.text(4.0, 4.05, "posts / owns", fontsize=7.5, fontstyle='italic', ha='center', color="#475569")

    ax.annotate('', xy=(8.7, 5.1), xytext=(7.7, 4.7),
                arrowprops=dict(arrowstyle="-|>", color="#B45309", lw=1.8))
    ax.text(7.85, 4.85, "1", fontsize=9, fontweight='bold', color="#B45309")
    ax.text(8.5, 5.2, "N", fontsize=9, fontweight='bold', color="#B45309")

    ax.annotate('', xy=(8.7, 2.1), xytext=(7.7, 2.5),
                arrowprops=dict(arrowstyle="-|>", color="#6D28D9", lw=1.8))
    ax.text(7.85, 2.6, "1", fontsize=9, fontweight='bold', color="#6D28D9")
    ax.text(8.5, 2.0, "N", fontsize=9, fontweight='bold', color="#6D28D9")

    ax.annotate('', xy=(8.7, 1.4), xytext=(3.5, 2.8),
                arrowprops=dict(arrowstyle="-|>", connectionstyle="arc3,rad=-0.25", color="#6D28D9", lw=1.5, ls="--"))
    ax.text(4.8, 1.15, "receives (1:N)", fontsize=7.5, fontstyle='italic', color="#6D28D9")

    # Legend
    leg_box = patches.FancyBboxPatch((4.5, 0.4), 3.2, 0.9, boxstyle="round,pad=0.03,rounding_size=0.08",
                                     facecolor='#F1F5F9', edgecolor='#CBD5E1', linewidth=1)
    ax.add_patch(leg_box)
    ax.text(4.65, 1.05, "Integrity Rules & Cascades:", fontsize=7.5, fontweight='bold', color='#1E293B')
    ax.text(4.65, 0.85, "- users -> items: ON DELETE CASCADE", fontsize=7, color='#475569')
    ax.text(4.65, 0.65, "- items -> reports: ON DELETE CASCADE", fontsize=7, color='#475569')
    ax.text(4.65, 0.48, "- items -> notifications: ON DELETE SET NULL", fontsize=7, color='#475569')

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated ERD Diagram: {output_path}")

def create_ui_composite_1(output_path):
    fig, axes = plt.subplots(1, 2, figsize=(12, 5.2), dpi=300)
    fig.patch.set_facecolor('#F8FAFC')

    # Left: Auth
    ax1 = axes[0]
    ax1.axis('off')
    ax1.set_xlim(0, 10)
    ax1.set_ylim(0, 10)

    card1 = patches.FancyBboxPatch((0.2, 0.2), 9.6, 9.6, boxstyle="round,pad=0.1,rounding_size=0.3",
                                  facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1.5)
    ax1.add_patch(card1)
    w_hdr1 = patches.FancyBboxPatch((0.2, 9.0), 9.6, 0.8, boxstyle="round,pad=0.05,rounding_size=0.15",
                                   facecolor='#1E293B', edgecolor='#1E293B')
    ax1.add_patch(w_hdr1)
    ax1.text(0.6, 9.4, "O O O", fontsize=8.5, color='#94A3B8')
    ax1.text(5.0, 9.4, "unithrift.campus / auth.php (Secure Student Portal)", ha='center', fontsize=7.5, color='#F8FAFC')

    ax1.text(5.0, 8.4, "[Portal] UniThrift Campus Login", ha='center', fontsize=12, fontweight='bold', color='#0F172A')
    ax1.text(5.0, 8.0, "Sign in with your verified university credentials", ha='center', fontsize=7.5, color='#64748B')

    tab_active = patches.FancyBboxPatch((1.5, 7.3), 3.4, 0.5, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#2563EB', edgecolor='#2563EB')
    tab_inactive = patches.FancyBboxPatch((5.1, 7.3), 3.4, 0.5, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#F1F5F9', edgecolor='#E2E8F0')
    ax1.add_patch(tab_active)
    ax1.add_patch(tab_inactive)
    ax1.text(3.2, 7.55, "Sign In", ha='center', va='center', fontsize=8, fontweight='bold', color='#FFFFFF')
    ax1.text(6.8, 7.55, "Student Register", ha='center', va='center', fontsize=8, fontweight='bold', color='#64748B')

    ax1.text(1.5, 6.75, "Student ID / Campus Email", fontsize=7.5, fontweight='bold', color='#334155')
    fld1 = patches.FancyBboxPatch((1.5, 6.0), 7.0, 0.6, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#FFFFFF', edgecolor='#CBD5E1')
    ax1.add_patch(fld1)
    ax1.text(1.8, 6.3, "2023200000732@seu.edu.bd", fontsize=8, color='#0F172A')

    ax1.text(1.5, 5.55, "Account Password", fontsize=7.5, fontweight='bold', color='#334155')
    fld2 = patches.FancyBboxPatch((1.5, 4.8), 7.0, 0.6, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#FFFFFF', edgecolor='#CBD5E1')
    ax1.add_patch(fld2)
    ax1.text(1.8, 5.1, "***************", fontsize=8, color='#0F172A')

    sec_pill = patches.FancyBboxPatch((1.5, 3.9), 7.0, 0.65, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#ECFDF5', edgecolor='#A7F3D0')
    ax1.add_patch(sec_pill)
    ax1.text(1.8, 4.35, "[SECURE] BCRYPT Salted Hash Verification Active", fontsize=7.5, fontweight='bold', color='#065F46')
    ax1.text(1.8, 4.05, "Sessions guarded with HTTP-Only & SameSite strict flags", fontsize=6.8, color='#047857')

    btn = patches.FancyBboxPatch((1.5, 2.7), 7.0, 0.8, boxstyle="round,pad=0.05,rounding_size=0.1", facecolor='#2563EB', edgecolor='#1D4ED8')
    ax1.add_patch(btn)
    ax1.text(5.0, 3.1, "Access Marketplace Dashboard -->", ha='center', va='center', fontsize=9.5, fontweight='bold', color='#FFFFFF')

    demo_box = patches.FancyBboxPatch((1.5, 0.7), 7.0, 1.5, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#FFFBEB', edgecolor='#FDE68A')
    ax1.add_patch(demo_box)
    ax1.text(5.0, 1.85, "Pre-Seeded Academic Test Accounts:", ha='center', fontsize=7.5, fontweight='bold', color='#92400E')
    ax1.text(5.0, 1.45, "Admin: admin@seu.edu.bd | Pass: admin123", ha='center', fontsize=7.2, color='#B45309')
    ax1.text(5.0, 1.05, "Student: 2021100000145 | Pass: student123", ha='center', fontsize=7.2, color='#B45309')

    # Right: Marketplace
    ax2 = axes[1]
    ax2.axis('off')
    ax2.set_xlim(0, 10)
    ax2.set_ylim(0, 10)

    card2 = patches.FancyBboxPatch((0.2, 0.2), 9.6, 9.6, boxstyle="round,pad=0.1,rounding_size=0.3",
                                  facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1.5)
    ax2.add_patch(card2)
    w_hdr2 = patches.FancyBboxPatch((0.2, 9.0), 9.6, 0.8, boxstyle="round,pad=0.05,rounding_size=0.15",
                                   facecolor='#1E293B', edgecolor='#1E293B')
    ax2.add_patch(w_hdr2)
    ax2.text(0.6, 9.4, "O O O", fontsize=8.5, color='#94A3B8')
    ax2.text(5.0, 9.4, "unithrift.campus / marketplace.php (Live Catalogue)", ha='center', fontsize=7.5, color='#F8FAFC')

    sbar = patches.FancyBboxPatch((0.6, 8.0), 8.8, 0.7, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#F1F5F9', edgecolor='#CBD5E1')
    ax2.add_patch(sbar)
    ax2.text(0.9, 8.35, "[Search]  Textbooks, Arduino, course codes (e.g., CSE 311)...", fontsize=7.5, color='#64748B')

    pills = [("All Items", '#0F172A', '#FFFFFF'), ("Textbooks", '#EFF6FF', '#1D4ED8'), 
             ("Lab Kits", '#EFF6FF', '#1D4ED8'), ("Calculators", '#EFF6FF', '#1D4ED8'), 
             ("Drawing", '#EFF6FF', '#1D4ED8')]
    px = 0.6
    for ptext, bg, fg in pills:
        p_patch = patches.FancyBboxPatch((px, 7.2), 1.6, 0.5, boxstyle="round,pad=0.03,rounding_size=0.15", facecolor=bg, edgecolor='#CBD5E1')
        ax2.add_patch(p_patch)
        ax2.text(px + 0.8, 7.45, ptext, ha='center', va='center', fontsize=7, fontweight='bold', color=fg)
        px += 1.8

    # Card A: CLRS Textbook
    ca = patches.FancyBboxPatch((0.6, 3.8), 4.2, 3.1, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#FFFFFF', edgecolor='#E2E8F0', linewidth=1.2)
    ax2.add_patch(ca)
    ax2.text(0.9, 6.45, "[Course: CSE 311]", fontsize=7, fontweight='bold', color='#2563EB')
    ax2.text(0.9, 6.05, "Algorithms (CLRS 3rd Ed)", fontsize=8, fontweight='bold', color='#0F172A')
    ax2.text(0.9, 5.65, "Condition: Gently Used", fontsize=6.8, color='#059669')
    ax2.text(0.9, 5.15, "Resale: BDT 450", fontsize=9.5, fontweight='bold', color='#DC2626')
    ax2.text(2.6, 5.15, "Orig: BDT 1200 (-63%)", fontsize=6.8, color='#64748B')
    btn_a = patches.FancyBboxPatch((0.9, 4.1), 3.6, 0.5, boxstyle="round,pad=0.02,rounding_size=0.08", facecolor='#0D9488', edgecolor='#0D9488')
    ax2.add_patch(btn_a)
    ax2.text(2.7, 4.35, "View Details & WhatsApp", ha='center', va='center', fontsize=7, fontweight='bold', color='#FFFFFF')

    # Card B: Arduino Uno
    cb = patches.FancyBboxPatch((5.2, 3.8), 4.2, 3.1, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#FFFFFF', edgecolor='#E2E8F0', linewidth=1.2)
    ax2.add_patch(cb)
    ax2.text(5.5, 6.45, "[Course: CSE 316]", fontsize=7, fontweight='bold', color='#2563EB')
    ax2.text(5.5, 6.05, "Arduino Uno Starter Kit", fontsize=8, fontweight='bold', color='#0F172A')
    ax2.text(5.5, 5.65, "Condition: Like New", fontsize=6.8, color='#059669')
    ax2.text(5.5, 5.15, "Resale: BDT 1250", fontsize=9.5, fontweight='bold', color='#DC2626')
    ax2.text(7.3, 5.15, "Orig: BDT 2800 (-55%)", fontsize=6.8, color='#64748B')
    btn_b = patches.FancyBboxPatch((5.5, 4.1), 3.6, 0.5, boxstyle="round,pad=0.02,rounding_size=0.08", facecolor='#0D9488', edgecolor='#0D9488')
    ax2.add_patch(btn_b)
    ax2.text(7.3, 4.35, "View Details & WhatsApp", ha='center', va='center', fontsize=7, fontweight='bold', color='#FFFFFF')

    # Impact Banner
    bot_box = patches.FancyBboxPatch((0.6, 0.6), 8.8, 2.8, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#F0FDF4', edgecolor='#BBF7D0')
    ax2.add_patch(bot_box)
    ax2.text(5.0, 3.0, "[Impact] Sustainable Campus Resource Exchange Metric", ha='center', fontsize=8, fontweight='bold', color='#166534')
    ax2.text(5.0, 2.5, "Over BDT 18,500 Saved by SEU Students in Active Resales", ha='center', fontsize=9, fontweight='bold', color='#15803D')
    ax2.text(5.0, 2.0, "Peer Handover Points: SEU Cafeteria, CSE Lab 4, Library 3rd Floor", ha='center', fontsize=7.2, color='#166534')
    ax2.text(5.0, 1.4, "Instant Debounce Filter: Responds < 50ms without full HTTP page refresh", ha='center', fontsize=7, fontstyle='italic', color='#15803D')
    ax2.text(5.0, 0.9, "Theme Switcher: Fully responsive dark mode & light mode toggle", ha='center', fontsize=7, color='#166534')

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated UI Composite 1: {output_path}")

def create_ui_composite_2(output_path):
    fig, axes = plt.subplots(1, 2, figsize=(12, 5.2), dpi=300)
    fig.patch.set_facecolor('#F8FAFC')

    # Left: Seller Studio CRUD
    ax1 = axes[0]
    ax1.axis('off')
    ax1.set_xlim(0, 10)
    ax1.set_ylim(0, 10)

    card1 = patches.FancyBboxPatch((0.2, 0.2), 9.6, 9.6, boxstyle="round,pad=0.1,rounding_size=0.3",
                                  facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1.5)
    ax1.add_patch(card1)
    w_hdr1 = patches.FancyBboxPatch((0.2, 9.0), 9.6, 0.8, boxstyle="round,pad=0.05,rounding_size=0.15",
                                   facecolor='#1E293B', edgecolor='#1E293B')
    ax1.add_patch(w_hdr1)
    ax1.text(0.6, 9.4, "O O O", fontsize=8.5, color='#94A3B8')
    ax1.text(5.0, 9.4, "unithrift.campus / my_listings.php (Seller Studio & CRUD Hub)", ha='center', fontsize=7.5, color='#F8FAFC')

    ax1.text(0.6, 8.4, "[Inventory] My Listed Academic Gear", fontsize=11, fontweight='bold', color='#0F172A')
    btn_add = patches.FancyBboxPatch((6.8, 8.1), 2.6, 0.6, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#16A34A', edgecolor='#15803D')
    ax1.add_patch(btn_add)
    ax1.text(8.1, 8.4, "+ Post New Item", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#FFFFFF')

    stats = [("Active Listings", "4 Items", '#EFF6FF', '#1D4ED8'), 
             ("Items Sold", "2 Items", '#F0FDF4', '#15803D'), 
             ("Earnings Generated", "BDT 2,450", '#FEF3C7', '#B45309')]
    sx = 0.6
    for stitle, sval, sbg, sfg in stats:
        sbox = patches.FancyBboxPatch((sx, 7.1), 2.8, 0.75, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor=sbg, edgecolor='#CBD5E1')
        ax1.add_patch(sbox)
        ax1.text(sx + 0.2, 7.55, stitle, fontsize=6.8, color='#475569')
        ax1.text(sx + 0.2, 7.25, sval, fontsize=9, fontweight='bold', color=sfg)
        sx += 3.0

    th_box = patches.FancyBboxPatch((0.6, 6.4), 8.8, 0.45, boxstyle="square,pad=0", facecolor='#F1F5F9', edgecolor='#E2E8F0')
    ax1.add_patch(th_box)
    ax1.text(0.8, 6.55, "Item Title & Course", fontsize=7, fontweight='bold', color='#475569')
    ax1.text(4.2, 6.55, "Price", fontsize=7, fontweight='bold', color='#475569')
    ax1.text(5.5, 6.55, "Status", fontsize=7, fontweight='bold', color='#475569')
    ax1.text(7.6, 6.55, "Actions (CRUD)", fontsize=7, fontweight='bold', color='#475569')

    rows = [
        ("CLRS Algorithms (CSE 311)", "BDT 450", "Available", '#DCFCE7', '#15803D'),
        ("Casio fx-991EX (MAT 101)", "BDT 1100", "Reserved", '#FEF9C3', '#A16207'),
        ("Digital Multimeter (EEE 102)", "BDT 300", "Sold", '#F1F5F9', '#475569'),
        ("Breadboard + ICs (CSE 225)", "BDT 200", "Available", '#DCFCE7', '#15803D')
    ]
    ry = 5.7
    for title, price, status, bg_s, fg_s in rows:
        row_box = patches.FancyBboxPatch((0.6, ry - 0.1), 8.8, 0.65, boxstyle="square,pad=0", facecolor='#FFFFFF', edgecolor='#E2E8F0')
        ax1.add_patch(row_box)
        ax1.text(0.8, ry + 0.2, title, fontsize=7.2, fontweight='bold', color='#0F172A')
        ax1.text(4.2, ry + 0.2, price, fontsize=7.2, color='#0F172A')
        
        badge = patches.FancyBboxPatch((5.4, ry + 0.05), 1.5, 0.38, boxstyle="round,pad=0.02,rounding_size=0.08", facecolor=bg_s, edgecolor=fg_s)
        ax1.add_patch(badge)
        ax1.text(6.15, ry + 0.24, status, ha='center', va='center', fontsize=6.8, fontweight='bold', color=fg_s)

        ax1.text(7.5, ry + 0.2, "[Edit]", fontsize=7, fontweight='bold', color='#2563EB')
        ax1.text(8.3, ry + 0.2, "[Delete]", fontsize=7, fontweight='bold', color='#DC2626')
        ry -= 0.75

    sec_call = patches.FancyBboxPatch((0.6, 0.6), 8.8, 1.8, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#FEF2F2', edgecolor='#FECACA')
    ax1.add_patch(sec_call)
    ax1.text(0.9, 2.05, "[SECURITY] Strict Server-Side Ownership Guard (Access Control):", fontsize=7.5, fontweight='bold', color='#991B1B')
    ax1.text(0.9, 1.65, "Every update/delete action executes: SELECT * FROM items WHERE id=? AND user_id=?", fontsize=7, color='#7F1D1D')
    ax1.text(0.9, 1.25, "Cross-account tampering or unauthorized ID spoofing is terminated with HTTP 403 Forbidden.", fontsize=7, color='#7F1D1D')
    ax1.text(0.9, 0.85, "CSRF Token validation on all mutating POST requests ensures immunity to forged attacks.", fontsize=7, color='#7F1D1D')

    # Right: Peer Contact & Savings Calculator
    ax2 = axes[1]
    ax2.axis('off')
    ax2.set_xlim(0, 10)
    ax2.set_ylim(0, 10)

    card2 = patches.FancyBboxPatch((0.2, 0.2), 9.6, 9.6, boxstyle="round,pad=0.1,rounding_size=0.3",
                                  facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1.5)
    ax2.add_patch(card2)
    w_hdr2 = patches.FancyBboxPatch((0.2, 9.0), 9.6, 0.8, boxstyle="round,pad=0.05,rounding_size=0.15",
                                   facecolor='#1E293B', edgecolor='#1E293B')
    ax2.add_patch(w_hdr2)
    ax2.text(0.6, 9.4, "O O O", fontsize=8.5, color='#94A3B8')
    ax2.text(5.0, 9.4, "unithrift.campus / item_details.php & calculator.php", ha='center', fontsize=7.5, color='#F8FAFC')

    ax2.text(0.6, 8.4, "[Communication] Peer Contact & Handover Safety", fontsize=10.5, fontweight='bold', color='#0F172A')
    
    wa_box = patches.FancyBboxPatch((0.6, 6.7), 8.8, 1.5, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#ECFDF5', edgecolor='#6EE7B7')
    ax2.add_patch(wa_box)
    ax2.text(0.9, 7.8, ">> 1-Click Direct WhatsApp Integration (wa.me API):", fontsize=7.5, fontweight='bold', color='#065F46')
    ax2.text(0.9, 7.4, "Pre-formatted message generated automatically with item title, course code and offer price.", fontsize=6.8, color='#047857')
    ax2.text(0.9, 7.0, "\"Hi, I saw your listing for 'CLRS 3rd Ed' (BDT 450) on UniThrift. Is it available for campus handover?\"", fontsize=6.5, fontstyle='italic', color='#065F46')

    call_btn = patches.FancyBboxPatch((0.6, 5.9), 4.2, 0.65, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#25D366', edgecolor='#1EBE5D')
    ax2.add_patch(call_btn)
    ax2.text(2.7, 6.22, "WhatsApp Seller Directly", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#FFFFFF')

    tel_btn = patches.FancyBboxPatch((5.2, 5.9), 4.2, 0.65, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#2563EB', edgecolor='#1D4ED8')
    ax2.add_patch(tel_btn)
    ax2.text(7.3, 6.22, "Call Seller (tel: link)", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#FFFFFF')

    calc_box = patches.FancyBboxPatch((0.6, 0.6), 8.8, 4.9, boxstyle="round,pad=0.03,rounding_size=0.1", facecolor='#F8FAFC', edgecolor='#CBD5E1')
    ax2.add_patch(calc_box)
    ax2.text(5.0, 5.15, "[Tool] Interactive Academic Savings Calculator", ha='center', fontsize=10, fontweight='bold', color='#0F172A')
    ax2.text(5.0, 4.75, "Select your enrolled courses to calculate estimated semester savings", ha='center', fontsize=7.2, color='#64748B')

    cb1 = patches.FancyBboxPatch((1.0, 3.4), 3.8, 1.1, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#FEF2F2', edgecolor='#FECACA')
    cb2 = patches.FancyBboxPatch((5.2, 3.4), 3.8, 1.1, boxstyle="round,pad=0.03,rounding_size=0.08", facecolor='#F0FDF4', edgecolor='#BBF7D0')
    ax2.add_patch(cb1)
    ax2.add_patch(cb2)
    ax2.text(2.9, 4.15, "Brand New Retail Cost", ha='center', fontsize=7, color='#991B1B')
    ax2.text(2.9, 3.75, "BDT 8,400", ha='center', fontsize=11, fontweight='bold', color='#DC2626')
    ax2.text(7.1, 4.15, "UniThrift ReUse Price", ha='center', fontsize=7, color='#166534')
    ax2.text(7.1, 3.75, "BDT 3,250", ha='center', fontsize=11, fontweight='bold', color='#16A34A')

    sav_pill = patches.FancyBboxPatch((1.0, 1.7), 8.0, 1.4, boxstyle="round,pad=0.05,rounding_size=0.1", facecolor='#0F172A', edgecolor='#0F172A')
    ax2.add_patch(sav_pill)
    ax2.text(5.0, 2.7, "TOTAL ESTIMATED STUDENT SEMESTER SAVINGS", ha='center', fontsize=7.5, fontweight='bold', color='#38BDF8')
    ax2.text(5.0, 2.2, "BDT 5,150 (61.3% Academic Cost Reduction)", ha='center', fontsize=11, fontweight='bold', color='#4ADE80')
    ax2.text(5.0, 1.9, "Environmental Impact: 12.4 kg CO2e saved through equipment circularity", ha='center', fontsize=6.8, color='#94A3B8')

    ax2.text(5.0, 1.1, "Dynamic JavaScript slider updates price comparisons instantly on client-side.", ha='center', fontsize=6.8, fontstyle='italic', color='#64748B')

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated UI Composite 2: {output_path}")

if __name__ == '__main__':
    os.makedirs('docs_assets', exist_ok=True)
    create_architecture_diagram('docs_assets/diagram_architecture.png')
    create_erd_diagram('docs_assets/diagram_erd.png')
    create_ui_composite_1('docs_assets/diagram_ui_part1.png')
    create_ui_composite_2('docs_assets/diagram_ui_part2.png')
