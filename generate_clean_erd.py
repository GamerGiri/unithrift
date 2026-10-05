import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches

# Set clean fonts and backend
plt.switch_backend('Agg')
plt.rcParams['font.sans-serif'] = ['Segoe UI', 'Arial', 'Calibri', 'DejaVu Sans']
plt.rcParams['axes.unicode_minus'] = False

def generate_clean_erd(output_path):
    fig, ax = plt.subplots(figsize=(14, 10.0), dpi=300)
    ax.set_xlim(0, 14)
    ax.set_ylim(0, 10.0)
    ax.axis('off')

    fig.patch.set_facecolor('#FFFFFF')
    ax.set_facecolor('#FFFFFF')

    # Main Diagram Title & Subtitle (Raised with extra spacing)
    ax.text(7.0, 9.65, "UniThrift Relational Entity-Relationship Diagram (ERD)", 
            ha='center', va='center', fontsize=16, fontweight='bold', color='#0F172A')
    ax.text(7.0, 9.32, "MariaDB / MySQL Normalized Schema (Crow's Foot Notation & Foreign Key Constraints)", 
            ha='center', va='center', fontsize=10, fontstyle='italic', color='#475569')

    # Helper function to draw an entity table with strict bounds and proper padding
    def draw_entity_table(x, y, w, h, table_name, pk_list, fk_list, attr_list, header_color):
        outer_box = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0,rounding_size=0.14",
                                          facecolor='#FFFFFF', edgecolor='#334155', linewidth=1.6)
        ax.add_patch(outer_box)

        hdr_h = 0.58
        header_box = patches.FancyBboxPatch((x, y + h - hdr_h), w, hdr_h, boxstyle="round,pad=0,rounding_size=0.12",
                                           facecolor=header_color, edgecolor=header_color, linewidth=1)
        ax.add_patch(header_box)
        filler = patches.Rectangle((x, y + h - hdr_h), w, 0.15, facecolor=header_color, edgecolor=header_color)
        ax.add_patch(filler)

        ax.text(x + w / 2.0, y + h - 0.30, table_name, ha='center', va='center', 
                fontsize=11, fontweight='bold', color='#FFFFFF')

        curr_y = y + h - 0.88
        line_spacing = 0.28

        for pk in pk_list:
            ax.text(x + 0.18, curr_y, "[PK]", fontsize=7.8, fontweight='bold', color='#B91C1C')
            ax.text(x + 0.72, curr_y, pk, fontsize=7.8, fontweight='bold', color='#0F172A')
            curr_y -= line_spacing

        for fk in fk_list:
            ax.text(x + 0.18, curr_y, "[FK]", fontsize=7.8, fontweight='bold', color='#2563EB')
            ax.text(x + 0.72, curr_y, fk, fontsize=7.8, fontstyle='italic', color='#0F172A')
            curr_y -= line_spacing

        for attr, attr_type in attr_list:
            ax.text(x + 0.22, curr_y, "-", fontsize=8, color='#64748B')
            ax.text(x + 0.42, curr_y, attr, fontsize=7.5, color='#1E293B')
            ax.text(x + w - 0.18, curr_y, attr_type, ha='right', fontsize=7.2, color='#64748B')
            curr_y -= line_spacing

    # 1. ENTITY: users (Top Left)
    draw_entity_table(
        x=0.6, y=4.2, w=3.8, h=4.5,
        table_name="users",
        pk_list=["id : INT (Auto-Inc)"],
        fk_list=[],
        attr_list=[
            ("student_id", "VARCHAR(30) [UK]"),
            ("full_name", "VARCHAR(100)"),
            ("email", "VARCHAR(100) [UK]"),
            ("phone", "VARCHAR(25)"),
            ("department", "VARCHAR(50)"),
            ("role", "VARCHAR(20) [student/admin]"),
            ("password_hash", "VARCHAR(255)"),
            ("created_at", "TIMESTAMP")
        ],
        header_color="#1E3A8A"
    )

    # 2. ENTITY: contact_messages (Bottom Left)
    draw_entity_table(
        x=0.6, y=0.4, w=3.8, h=3.4,
        table_name="contact_messages",
        pk_list=["id : INT (Auto-Inc)"],
        fk_list=[],
        attr_list=[
            ("name", "VARCHAR(100)"),
            ("email", "VARCHAR(100)"),
            ("phone", "VARCHAR(30)"),
            ("subject", "VARCHAR(150)"),
            ("message", "TEXT"),
            ("status", "ENUM('read','unread')"),
            ("created_at", "TIMESTAMP")
        ],
        header_color="#334155"
    )

    # 3. ENTITY: items (Center)
    draw_entity_table(
        x=5.0, y=2.6, w=4.2, h=6.1,
        table_name="items",
        pk_list=["id : INT (Auto-Inc)"],
        fk_list=["user_id : INT (users.id)"],
        attr_list=[
            ("title", "VARCHAR(150)"),
            ("category", "ENUM(5 categories)"),
            ("course_code", "VARCHAR(20)"),
            ("item_condition", "ENUM(3 types)"),
            ("original_price", "DECIMAL(10,2)"),
            ("selling_price", "DECIMAL(10,2)"),
            ("description", "TEXT"),
            ("meetup_location", "VARCHAR(120)"),
            ("image_url", "VARCHAR(255)"),
            ("status", "ENUM('Available'..)"),
            ("created_at", "TIMESTAMP"),
            ("updated_at", "TIMESTAMP")
        ],
        header_color="#0D9488"
    )

    # 4. ENTITY: reports (Top Right)
    draw_entity_table(
        x=9.8, y=4.8, w=3.6, h=3.9,
        table_name="reports",
        pk_list=["id : INT (Auto-Inc)"],
        fk_list=[
            ("item_id : INT (items.id)"),
            ("reporter_id : INT (users.id)")
        ],
        attr_list=[
            ("reason", "VARCHAR(100)"),
            ("details", "TEXT"),
            ("status", "ENUM('Pending'..)"),
            ("admin_notes", "TEXT"),
            ("created_at", "TIMESTAMP")
        ],
        header_color="#B45309"
    )

    # 5. ENTITY: notifications (Bottom Right)
    draw_entity_table(
        x=9.8, y=0.4, w=3.6, h=4.0,
        table_name="notifications",
        pk_list=["id : INT (Auto-Inc)"],
        fk_list=[
            ("user_id : INT (users.id)"),
            ("item_id : INT (items.id)")
        ],
        attr_list=[
            ("title", "VARCHAR(150)"),
            ("message", "TEXT"),
            ("target_audience", "VARCHAR(30)"),
            ("alert_type", "VARCHAR(20)"),
            ("is_read", "TINYINT(1)"),
            ("created_at", "TIMESTAMP")
        ],
        header_color="#6D28D9"
    )

    # 6. LEGEND & INTEGRITY RULES (Center Bottom)
    leg_box = patches.FancyBboxPatch((4.8, 0.4), 4.6, 1.85, boxstyle="round,pad=0,rounding_size=0.12",
                                     facecolor='#F8FAFC', edgecolor='#94A3B8', linewidth=1.2)
    ax.add_patch(leg_box)
    
    ax.text(4.8 + 2.3, 2.05, "Referential Integrity Rules & Cascades", 
            ha='center', va='center', fontsize=8.5, fontweight='bold', color='#1E293B')
    
    rules = [
        "- users -> items : ON DELETE CASCADE",
        "- items -> reports : ON DELETE CASCADE",
        "- users -> reports : ON DELETE SET NULL",
        "- users -> notifications : ON DELETE CASCADE",
        "- items -> notifications : ON DELETE SET NULL"
    ]
    ry = 1.75
    for r in rules:
        ax.text(5.0, ry, r, fontsize=7.2, color='#334155')
        ry -= 0.24

    ax.text(5.0, 0.58, "Notation: [PK] Primary Key | [FK] Foreign Key | [UK] Unique Key | 1:N One-to-Many", 
            fontsize=6.8, fontstyle='italic', color='#64748B')

    # RELATIONSHIP CONNECTORS

    # Connection 1: users (1) ---> (N) items
    ax.annotate('', xy=(5.0, 6.45), xytext=(4.4, 6.45),
                arrowprops=dict(arrowstyle="-|>", color="#1E3A8A", lw=2, mutation_scale=14))
    ax.text(4.45, 6.62, "1", fontsize=9.5, fontweight='bold', color="#1E3A8A")
    ax.text(4.85, 6.62, "N", fontsize=9.5, fontweight='bold', color="#1E3A8A")
    ax.text(4.7, 6.25, "posts / owns", fontsize=7.5, fontstyle='italic', ha='center', color="#475569")

    # Connection 2: items (1) ---> (N) reports
    ax.annotate('', xy=(9.8, 6.75), xytext=(9.2, 6.75),
                arrowprops=dict(arrowstyle="-|>", color="#B45309", lw=2, mutation_scale=14))
    ax.text(9.28, 6.92, "1", fontsize=9.5, fontweight='bold', color="#B45309")
    ax.text(9.65, 6.92, "N", fontsize=9.5, fontweight='bold', color="#B45309")
    ax.text(9.5, 6.55, "targeted by", fontsize=7.5, fontstyle='italic', ha='center', color="#475569")

    # Connection 3: items (1) ---> (N) notifications
    ax.annotate('', xy=(9.8, 3.2), xytext=(9.2, 3.2),
                arrowprops=dict(arrowstyle="-|>", color="#6D28D9", lw=2, mutation_scale=14))
    ax.text(9.28, 3.38, "1", fontsize=9.5, fontweight='bold', color="#6D28D9")
    ax.text(9.65, 3.38, "N", fontsize=9.5, fontweight='bold', color="#6D28D9")
    ax.text(9.5, 3.02, "references", fontsize=7.5, fontstyle='italic', ha='center', color="#475569")

    # Connection 4: users (1) ---> (N) reports (Clear space at y=8.95)
    path_x = [2.5, 2.5, 11.6, 11.6]
    path_y = [8.7, 8.95, 8.95, 8.7]
    ax.plot(path_x, path_y, color='#B45309', lw=1.6, linestyle='--')
    ax.annotate('', xy=(11.6, 8.7), xytext=(11.6, 8.75),
                arrowprops=dict(arrowstyle="-|>", color="#B45309", lw=1.6, mutation_scale=12))
    ax.text(2.6, 8.78, "1", fontsize=8.5, fontweight='bold', color="#B45309")
    ax.text(11.45, 8.78, "N", fontsize=8.5, fontweight='bold', color="#B45309")
    ax.text(7.0, 9.08, "users (1) ---> (N) reports (files report)", 
            ha='center', fontsize=7.8, fontstyle='italic', color="#B45309")

    # Connection 5: users (1) ---> (N) notifications (Clear corridor at y=2.48)
    c_x = [4.4, 4.65, 4.65, 9.8]
    c_y = [4.4, 4.4, 2.48, 2.48]
    ax.plot(c_x, c_y, color='#6D28D9', lw=1.6, linestyle='--')
    ax.annotate('', xy=(9.8, 2.48), xytext=(9.72, 2.48),
                arrowprops=dict(arrowstyle="-|>", color="#6D28D9", lw=1.6, mutation_scale=12))
    ax.text(4.45, 4.52, "1", fontsize=8.5, fontweight='bold', color="#6D28D9")
    ax.text(9.65, 2.58, "N", fontsize=8.5, fontweight='bold', color="#6D28D9")
    ax.text(7.1, 2.36, "users (1) ---> (N) notifications (receives alerts)", 
            ha='center', fontsize=7.5, fontstyle='italic', color="#6D28D9")

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated clean ERD without overlaps: {output_path}")

if __name__ == '__main__':
    generate_clean_erd('docs_assets/diagram_erd.png')
