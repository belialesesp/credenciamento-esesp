#!/usr/bin/env python3
"""
ESESP Monthly Diagram Generator
Shows category breakdown for a specific month
"""

import sys
import json
import matplotlib.pyplot as plt
import matplotlib.patches as patches
import numpy as np

def generate_monthly_diagram(data_file, output_file):
    """
    Generate monthly diagram showing:
    - If category='all': One bar per category for that month
    - If category=specific: One bar per course in that category/month
    """
    
    # Load data
    with open(data_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Extract values
    year = data.get('year', 2024)
    month = data.get('month', 1)
    month_name = data.get('month_name', 'Janeiro')
    category = data.get('category', 'all')
    items = data.get('items', [])  # List of {name, score, responses}
    
    # ESESP colors
    esesp_blue = '#1e3a5f'
    bar_color = '#0891b2'
    goal_line_color = '#dc2626'
    
    if len(items) == 0:
        # No data - create empty diagram
        fig, ax = plt.subplots(figsize=(12, 6))
        ax.text(0.5, 0.5, 'Nenhum dado disponível para este período',
                ha='center', va='center', fontsize=14, color='#6b7280')
        ax.set_xlim(0, 1)
        ax.set_ylim(0, 1)
        ax.axis('off')
        plt.tight_layout()
        plt.savefig(output_file, dpi=300, bbox_inches='tight', facecolor='white')
        plt.close()
        return True
    
    # Create figure
    fig, ax = plt.subplots(figsize=(14, 8))
    fig.patch.set_facecolor('white')
    
    # Prepare data
    names = [item['name'] for item in items]
    scores = [item['score'] for item in items]
    counts = [item['responses'] for item in items]
    
    # X positions
    x_pos = np.arange(len(names))
    
    # Determine bar colors based on score
    colors = []
    for score in scores:
        if score >= 90:
            colors.append('#059669')  # Green - Excellence
        elif score >= 75:
            colors.append('#0891b2')  # Cyan - Very Good
        elif score >= 60:
            colors.append('#f59e0b')  # Orange - Adequate
        else:
            colors.append('#dc2626')  # Red - Needs intervention
    
    # Plot bars
    bars = ax.bar(x_pos, scores, width=0.7, color=colors, 
                   alpha=0.85, edgecolor=esesp_blue, linewidth=1.5, zorder=5)
    
    # Add percentage labels on bars
    for i, (bar, score, count) in enumerate(zip(bars, scores, counts)):
        if score > 0:
            height = bar.get_height()
            # Percentage
            ax.text(bar.get_x() + bar.get_width()/2., height + 1.5,
                    f'{score:.1f}%',
                    ha='center', va='bottom', fontsize=10, fontweight='bold',
                    color=esesp_blue)
            
            # Response count inside bar if space
            if height > 8 and count > 0:
                ax.text(bar.get_x() + bar.get_width()/2., height/2,
                        f'{count}',
                        ha='center', va='center', fontsize=9,
                        color='white', fontweight='bold')
    
    # Goal line at 70%
    ax.axhline(y=70, color=goal_line_color, linestyle='--', linewidth=2.5, 
               label='Meta: 70%', zorder=10, alpha=0.7)
    
    # Add "META 70%" label
    ax.text(len(names) - 0.3, 71, 'META 70%', 
            fontsize=10, fontweight='bold', color=goal_line_color,
            bbox=dict(boxstyle='round,pad=0.5', facecolor='white', 
                     edgecolor=goal_line_color, linewidth=2))
    
    # Configure axes
    ax.set_ylabel('Pontuação (%)', fontsize=13, fontweight='bold', color=esesp_blue)
    ax.set_xticks(x_pos)
    
    # X-axis labels - truncate if too long
    labels = []
    for name in names:
        if len(name) > 30:
            labels.append(name[:27] + '...')
        else:
            labels.append(name)
    
    ax.set_xticklabels(labels, fontsize=9, fontweight='bold', rotation=45, ha='right')
    ax.set_ylim(0, 105)
    ax.set_yticks(range(0, 101, 10))
    
    # Grid
    ax.grid(axis='y', alpha=0.3, linestyle='-', linewidth=0.5)
    ax.set_axisbelow(True)
    
    # Title
    if category == 'all':
        title = f'{month_name}/{year} - Todas as Categorias'
    else:
        title = f'{month_name}/{year} - {category}'
    
    ax.text(0.5, 1.08, title, transform=ax.transAxes,
            fontsize=16, fontweight='bold', ha='center', color=esesp_blue)
    
    # Statistics box
    total_responses = sum(counts)
    avg_score = np.mean(scores) if scores else 0
    below_goal = sum(1 for s in scores if s < 70)
    
    stats_text = f'Total: {len(items)} itens\n'
    stats_text += f'Respostas: {total_responses}\n'
    stats_text += f'Média: {avg_score:.1f}%'
    if below_goal > 0:
        stats_text += f'\n⚠ {below_goal} abaixo de 70%'
    
    ax.text(0.98, 0.97, stats_text, transform=ax.transAxes,
            fontsize=10, ha='right', va='top',
            bbox=dict(boxstyle='round,pad=0.7', facecolor='#f0f9ff',
                     edgecolor=esesp_blue, linewidth=2),
            color=esesp_blue, fontweight='bold')
    
    # Legend for colors
    legend_elements = [
        patches.Patch(facecolor='#059669', label='Excelência (90-100%)'),
        patches.Patch(facecolor='#0891b2', label='Muito Bom (75-89%)'),
        patches.Patch(facecolor='#f59e0b', label='Adequado (60-74%)'),
        patches.Patch(facecolor='#dc2626', label='Necessita Intervenção (<60%)')
    ]
    ax.legend(handles=legend_elements, loc='upper left', fontsize=9, framealpha=0.9)
    
    # ESESP branding
    ax.text(0.02, 0.02, 'ESESP - Observatório', transform=ax.transAxes,
            fontsize=9, ha='left', va='bottom', color=esesp_blue, fontweight='bold')
    
    # Remove top and right spines
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.spines['left'].set_color(esesp_blue)
    ax.spines['bottom'].set_color(esesp_blue)
    ax.spines['left'].set_linewidth(1.5)
    ax.spines['bottom'].set_linewidth(1.5)
    
    # Adjust layout
    plt.tight_layout()
    plt.subplots_adjust(top=0.92, bottom=0.20)
    
    # Save
    plt.savefig(output_file, dpi=300, bbox_inches='tight',
                facecolor='white', edgecolor='none')
    plt.close()
    
    return True

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python3 generate_monthly_diagram.py <input_json> <output_png>")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    try:
        generate_monthly_diagram(input_file, output_file)
        print(f"Monthly diagram generated: {output_file}")
        sys.exit(0)
    except Exception as e:
        print(f"Error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)