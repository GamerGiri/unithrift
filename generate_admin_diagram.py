import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches

plt.switch_backend('Agg')
plt.rcParams['font.sans-serif'] = ['Segoe UI', 'Arial', 'Calibri', 'DejaVu Sans']
plt.rcParams['axes.unicode_minus'] = False

def generate_admin_panel_diagram(output_path):
    fig, ax = plt.subplots(figsize=(12, 6.0), dpi=300)
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 6.0)
    ax.axis('off')

    fig.patch.set_facecolor('#F8FAFC')
    ax.set_facecolor('#F8FAFC')

    # Main Browser Frame
    frame = patches.FancyBboxPatch((0.2, 0.2), 11.6, 5.6, boxstyle="round,pad=0,rounding_size=0.2",
                                  facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1.5)
    ax.add_patch(frame)

    # Browser Top Navigation Bar
    top_bar = patches.FancyBboxPatch((0.2, 5.3), 11.6, 0.5, boxstyle="round,pad=0,rounding_size=0.1",
                                    facecolor='#0F172A', edgecolor='#0F172A')
    ax.add_patch(top_bar)
    ax.text(0.5, 5.55, "O O O", fontsize=8, color='#94A3B8')
    ax.text(6.0, 5.55, "unithrift.campus / admin.php  (Administrator Command Center & Moderation Hub)", 
            ha='center', va='center', fontsize=8, color='#F8FAFC', fontweight='bold')

    # Admin Header & Role Badge
    ax.text(0.5, 4.95, "UniThrift Administration Console", fontsize=11, fontweight='bold', color='#1E293B')
    
    badge = patches.FancyBboxPatch((9.6, 4.8), 2.0, 0.35, boxstyle="round,pad=0,rounding_size=0.08",
                                   facecolor='#DCFCE7', edgecolor='#16A34A', linewidth=1)
    ax.add_patch(badge)
    ax.text(10.6, 4.97, "Role: Verified Admin", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#15803D')

    # Top KPI Metric Cards
    kpis = [
        ("Total Listings", "8 Items", "6 Avail | 2 Sold", "#EFF6FF", "#1D4ED8"),
        ("Registered Users", "4 Accounts", "3 Students | 1 Admin", "#F0FDF4", "#15803D"),
        ("Pending Reports", "1 Flagged", "Action Required", "#FEF2F2", "#B91C1C"),
        ("Inquiry Messages", "0 Unread", "All Inquiries Handled", "#FAF5FF", "#7C3AED")
    ]
    kx = 0.5
    for title, val, sub, bg, fg in kpis:
        k_box = patches.FancyBboxPatch((kx, 4.0), 2.6, 0.68, boxstyle="round,pad=0,rounding_size=0.08",
                                      facecolor=bg, edgecolor='#CBD5E1', linewidth=1)
        ax.add_patch(k_box)
        ax.text(kx + 0.15, 4.48, title, fontsize=7, color='#475569')
        ax.text(kx + 0.15, 4.22, val, fontsize=10, fontweight='bold', color=fg)
        ax.text(kx + 0.15, 4.08, sub, fontsize=6.2, color='#64748B')
        kx += 2.85

    # Navigation Tabs
    tabs = [
        ("Listings Moderation", True, "#1E3A8A"),
        ("User Management", False, "#64748B"),
        ("Safety Reports (1)", False, "#B91C1C"),
        ("Contact Inquiries", False, "#64748B"),
        ("Broadcast Alerts", False, "#64748B")
    ]
    tx = 0.5
    for t_text, is_active, col in tabs:
        t_bg = "#EFF6FF" if is_active else "#F1F5F9"
        t_border = "#2563EB" if is_active else "#CBD5E1"
        t_patch = patches.FancyBboxPatch((tx, 3.48), 2.1, 0.38, boxstyle="round,pad=0,rounding_size=0.06",
                                        facecolor=t_bg, edgecolor=t_border, linewidth=1.2 if is_active else 0.8)
        ax.add_patch(t_patch)
        ax.text(tx + 1.05, 3.67, t_text, ha='center', va='center', fontsize=7.2, fontweight='bold', color=col)
        tx += 2.3

    # Main Content Area: Left (Listings Moderation Table), Right (Actions & Broadcast Box)
    # Left Table Box (Width 7.0)
    tbl_box = patches.FancyBboxPatch((0.5, 0.4), 7.2, 2.9, boxstyle="round,pad=0,rounding_size=0.08",
                                    facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1)
    ax.add_patch(tbl_box)
    
    # Table Header inside box
    th = patches.Rectangle((0.5, 2.95), 7.2, 0.35, facecolor='#1E293B', edgecolor='#1E293B')
    ax.add_patch(th)
    ax.text(0.7, 3.12, "Item Title & Seller", fontsize=7, fontweight='bold', color='#FFFFFF')
    ax.text(3.7, 3.12, "Category", fontsize=7, fontweight='bold', color='#FFFFFF')
    ax.text(4.9, 3.12, "Price", fontsize=7, fontweight='bold', color='#FFFFFF')
    ax.text(5.7, 3.12, "Status", fontsize=7, fontweight='bold', color='#FFFFFF')
    ax.text(6.8, 3.12, "Admin Actions", fontsize=7, fontweight='bold', color='#FFFFFF')

    # Sample Listings Rows
    rows = [
        ("CLRS Algorithms (Tanvir Ahmed)", "Textbooks", "BDT 450", "Available", "#DCFCE7", "#15803D"),
        ("Arduino Starter Kit (Nusrat Jahan)", "Lab Gear", "BDT 1250", "Available", "#DCFCE7", "#15803D"),
        ("Casio fx-991EX (Sabbir Hossain)", "Electronics", "BDT 1100", "Reserved", "#FEF9C3", "#A16207"),
        ("Rotring Drawing Board (Sabbir H.)", "Drawing", "BDT 1300", "Available", "#DCFCE7", "#15803D"),
        ("Multimeter DT-830D (Nusrat Jahan)", "Lab Gear", "BDT 300", "Sold", "#F1F5F9", "#475569")
    ]
    ry = 2.65
    for title, cat, price, status, bg_s, fg_s in rows:
        row_bg = patches.Rectangle((0.5, ry - 0.08), 7.2, 0.36, facecolor='#FFFFFF', edgecolor='#F1F5F9')
        ax.add_patch(row_bg)
        ax.text(0.7, ry + 0.05, title, fontsize=6.8, color='#0F172A')
        ax.text(3.7, ry + 0.05, cat, fontsize=6.5, color='#475569')
        ax.text(4.9, ry + 0.05, price, fontsize=6.8, fontweight='bold', color='#0F172A')
        
        badge_s = patches.FancyBboxPatch((5.65, ry - 0.02), 0.95, 0.22, boxstyle="round,pad=0,rounding_size=0.04", facecolor=bg_s, edgecolor=fg_s)
        ax.add_patch(badge_s)
        ax.text(6.12, ry + 0.09, status, ha='center', va='center', fontsize=6, fontweight='bold', color=fg_s)

        ax.text(6.8, ry + 0.05, "[Edit]", fontsize=6.5, fontweight='bold', color='#2563EB')
        ax.text(7.2, ry + 0.05, "[Delete]", fontsize=6.5, fontweight='bold', color='#DC2626')
        ry -= 0.42

    # Bottom sub-caption inside table
    ax.text(0.7, 0.55, "Admins can override prices, edit descriptions, toggle states, or permanently remove listings.", 
            fontsize=6.5, fontstyle='italic', color='#64748B')

    # Right Panel: Safety Triage & Broadcast Notice Widget (Width 3.8)
    right_box = patches.FancyBboxPatch((7.9, 0.4), 3.8, 2.9, boxstyle="round,pad=0,rounding_size=0.08",
                                      facecolor='#FFFFFF', edgecolor='#CBD5E1', linewidth=1)
    ax.add_patch(right_box)

    # Section 1: Safety Report Triage
    rh1 = patches.Rectangle((7.9, 2.95), 3.8, 0.35, facecolor='#B45309', edgecolor='#B45309')
    ax.add_patch(rh1)
    ax.text(8.05, 3.12, "Active Safety Report Triage", fontsize=7, fontweight='bold', color='#FFFFFF')

    ax.text(8.05, 2.75, "Reported: CLRS Algorithms (Listing #1)", fontsize=6.8, fontweight='bold', color='#92400E')
    ax.text(8.05, 2.58, "Reason: Misleading edition information", fontsize=6.5, color='#78350F')
    ax.text(8.05, 2.42, "Reporter: Sabbir Hossain (Architecture)", fontsize=6.2, color='#64748B')

    btn_rem = patches.FancyBboxPatch((8.05, 2.05), 1.6, 0.28, boxstyle="round,pad=0,rounding_size=0.04", facecolor='#DC2626', edgecolor='#DC2626')
    ax.add_patch(btn_rem)
    ax.text(8.85, 2.19, "Remove Listing", ha='center', va='center', fontsize=6.2, fontweight='bold', color='#FFFFFF')

    btn_warn = patches.FancyBboxPatch((9.8, 2.05), 1.6, 0.28, boxstyle="round,pad=0,rounding_size=0.04", facecolor='#2563EB', edgecolor='#2563EB')
    ax.add_patch(btn_warn)
    ax.text(10.6, 2.19, "Notify Seller", ha='center', va='center', fontsize=6.2, fontweight='bold', color='#FFFFFF')

    # Section 2: Push Broadcast Engine
    rh2 = patches.Rectangle((7.9, 1.6), 3.8, 0.3, facecolor='#475569', edgecolor='#475569')
    ax.add_patch(rh2)
    ax.text(8.05, 1.75, "Campus Broadcast Notification Engine", fontsize=6.8, fontweight='bold', color='#FFFFFF')

    ax.text(8.05, 1.38, "Broadcast to: [All Users] | Type: [Warning]", fontsize=6.5, color='#334155')
    ax.text(8.05, 1.20, "\"Mid-term textbook rush: verify meetup spot\"", fontsize=6.2, fontstyle='italic', color='#0F172A')

    btn_push = patches.FancyBboxPatch((8.05, 0.65), 3.4, 0.4, boxstyle="round,pad=0,rounding_size=0.06", facecolor='#0D9488', edgecolor='#0D9488')
    ax.add_patch(btn_push)
    ax.text(9.75, 0.85, "Dispatch Campus-Wide Alert", ha='center', va='center', fontsize=7.2, fontweight='bold', color='#FFFFFF')

    plt.tight_layout()
    plt.savefig(output_path, dpi=300, bbox_inches='tight')
    plt.close()
    print(f"Generated Admin Panel Diagram: {output_path}")

if __name__ == '__main__':
    generate_admin_panel_diagram('docs_assets/diagram_admin_panel.png')
