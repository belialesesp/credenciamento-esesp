#!/usr/bin/env python3
"""
ESESP Diagram Generator - Bar Chart Style with Goal Line
Generates performance charts with 80% goal line and notifications
"""

import sys
import json
import matplotlib.pyplot as plt
import matplotlib.patches as patches
import numpy as np
from matplotlib.patches import Rectangle

def generate_diagram(data_file, output_file):
    """
    Generate bar chart diagram with:
    - 3 bars (Pedagógico, Didático, Infraestrutura)
    - Goal line at 80%
    - Percentages on bars
    - Weights shown (35%, 40%, 25%)
    - Overall score
    - Alert if below 80%
    - Highlight lowest score
    """
    
    # Load data
    with open(data_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Extract values
    course_name = data['course_name']
    docente_name = data['docente_name']
    overall_score = float(data['overall_score'])
    pedagogical = float(data['pedagogical_score'])
    didactic = float(data['didactic_score'])
    infrastructure = float(data['infrastructure_score'])
    response_count = int(data.get('response_count', 0))
    
    # Create figure with ESESP colors
    fig, ax = plt.subplots(figsize=(12, 8))
    fig.patch.set_facecolor('white')
    
    # ESESP color scheme
    esesp_blue = '#1e3a5f'
    esesp_cyan = '#0891b2'
    esesp_green = '#059669'
    goal_line_color = '#dc2626'
    warning_color = '#f59e0b'
    
    # Data for bars — Pedagógico 35%, Didático 40%, Infraestrutura 25%
    categories = ['Pedagógico\n(35%)', 'Didático\n(40%)', 'Infraestrutura\n(25%)']
    scores = [pedagogical, didactic, infrastructure]
    weights = [35, 40, 25]  # FIX: corrected from [40, 35, 25]
    
    # Determine lowest score
    lowest_idx = scores.index(min(scores))
    lowest_aspect = ['Pedagógico', 'Didático', 'Infraestrutura'][lowest_idx]
    lowest_score = min(scores)
    
    # Bar colors - highlight lowest
    bar_colors = []
    for i, score in enumerate(scores):
        if i == lowest_idx:
            if score < 80:
                bar_colors.append(goal_line_color)  # Red if below goal
            else:
                bar_colors.append(warning_color)  # Orange if lowest but above goal
        else:
            if score < 80:
                bar_colors.append(warning_color)  # Orange if below goal
            else:
                bar_colors.append(esesp_cyan)  # Cyan if good
    
    # X positions for bars
    x_pos = np.arange(len(categories))
    bar_width = 0.6
    
    # Create bars
    bars = ax.bar(x_pos, scores, bar_width, color=bar_colors, 
                   edgecolor=esesp_blue, linewidth=2, alpha=0.8)
    
    # Add percentage labels on bars
    for i, (bar, score) in enumerate(zip(bars, scores)):
        height = bar.get_height()
        if height < 15:
            y_pos = height + 3
            va = 'bottom'
        else:
            y_pos = height / 2
            va = 'center'
        
        ax.text(bar.get_x() + bar.get_width()/2., y_pos,
                f'{score:.1f}%',
                ha='center', va=va, fontsize=16, fontweight='bold',
                color='white' if height >= 15 else esesp_blue)
    
    # Goal line at 80%
    ax.axhline(y=80, color=goal_line_color, linestyle='--', linewidth=2.5, 
               label='Meta: 80%', zorder=10)
    
    # Add "META 80%" text near the line
    ax.text(len(categories) - 0.3, 71, 'META 80%', 
            fontsize=11, fontweight='bold', color=goal_line_color,
            bbox=dict(boxstyle='round,pad=0.5', facecolor='white', 
                     edgecolor=goal_line_color, linewidth=2))
    
    # Configure axes
    ax.set_ylabel('Pontuação (%)', fontsize=13, fontweight='bold', color=esesp_blue)
    ax.set_xlabel('Aspectos Avaliados', fontsize=13, fontweight='bold', color=esesp_blue)
    ax.set_xticks(x_pos)
    ax.set_xticklabels(categories, fontsize=11, fontweight='bold')
    ax.set_ylim(0, 105)
    ax.set_yticks(range(0, 101, 10))
    
    # Grid
    ax.grid(axis='y', alpha=0.3, linestyle='-', linewidth=0.5)
    ax.set_axisbelow(True)
    
    # Title
    ax.text(0.5, 1.12, course_name, transform=ax.transAxes,
            fontsize=16, fontweight='bold', ha='center', color=esesp_blue)
    ax.text(0.5, 1.06, f'Docente: {docente_name}', transform=ax.transAxes,
            fontsize=12, ha='center', color=esesp_blue, style='italic')
    
    # Overall score box — FIX: thresholds updated (média now 80%)
    if overall_score >= 90:
        overall_color = esesp_green
        overall_text = 'EXCELÊNCIA'
    elif overall_score >= 80:
        overall_color = esesp_cyan
        overall_text = 'MUITO BOM'
    elif overall_score >= 70:
        overall_color = warning_color
        overall_text = 'ADEQUADO'
    else:
        overall_color = goal_line_color
        overall_text = 'NECESSITA INTERVENÇÃO'
    
    bbox_props = dict(boxstyle='round,pad=0.8', facecolor=overall_color, 
                     edgecolor=esesp_blue, linewidth=2)
    ax.text(0.98, 0.97, f'Geral: {overall_score:.1f}%\n{overall_text}', 
            transform=ax.transAxes,
            fontsize=12, fontweight='bold', ha='right', va='top',
            color='white', bbox=bbox_props)
    
    ax.text(0.02, 0.97, f'Respostas: {response_count}', 
            transform=ax.transAxes,
            fontsize=11, ha='left', va='top',
            color=esesp_blue, fontweight='bold',
            bbox=dict(boxstyle='round,pad=0.5', facecolor='#f0f9ff', 
                     edgecolor=esesp_blue, linewidth=1))
    
    # Alert messages
    alert_messages = []
    if pedagogical < 80:
        alert_messages.append(f'⚠ Pedagógico abaixo da meta: {pedagogical:.1f}%')
    if didactic < 80:
        alert_messages.append(f'⚠ Didático abaixo da meta: {didactic:.1f}%')
    if infrastructure < 80:
        alert_messages.append(f'⚠ Infraestrutura abaixo da meta: {infrastructure:.1f}%')
    alert_messages.append(f'📊 Aspecto com menor nota: {lowest_aspect} ({lowest_score:.1f}%)')
    
    if alert_messages:
        alert_y = -0.15
        for msg in alert_messages:
            if '⚠' in msg:
                bg_color = '#fee2e2'
                edge_color = goal_line_color
            else:
                bg_color = '#fef3c7'
                edge_color = warning_color
            
            ax.text(0.5, alert_y, msg, transform=ax.transAxes,
                    fontsize=10, ha='center', va='top',
                    color=esesp_blue, fontweight='bold',
                    bbox=dict(boxstyle='round,pad=0.7', facecolor=bg_color,
                             edgecolor=edge_color, linewidth=2))
            alert_y -= 0.08
    
    # Footer — FIX: corrected from Pedagógico (40%) + Didático (35%)
    footer_text = f'Cálculo ponderado: Pedagógico (35%) + Didático (40%) + Infraestrutura (25%) = {overall_score:.1f}%'
    ax.text(0.5, -0.25 if alert_messages else -0.12, footer_text, 
            transform=ax.transAxes,
            fontsize=9, ha='center', va='top', color='#6b7280', style='italic')
    
    ax.text(0.02, -0.25 if alert_messages else -0.12, 'ESESP - Observatório', 
            transform=ax.transAxes,
            fontsize=9, ha='left', va='top', color=esesp_blue, fontweight='bold')
    
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.spines['left'].set_color(esesp_blue)
    ax.spines['bottom'].set_color(esesp_blue)
    ax.spines['left'].set_linewidth(1.5)
    ax.spines['bottom'].set_linewidth(1.5)
    
    plt.tight_layout()
    plt.subplots_adjust(top=0.90, bottom=0.20)
    
    plt.savefig(output_file, dpi=300, bbox_inches='tight', 
                facecolor='white', edgecolor='none')
    plt.close()
    
    return True

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python3 generate_diagram.py <input_json> <output_png>")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    try:
        generate_diagram(input_file, output_file)
        print(f"Diagram generated successfully: {output_file}")
        sys.exit(0)
    except Exception as e:
        print(f"Error generating diagram: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)